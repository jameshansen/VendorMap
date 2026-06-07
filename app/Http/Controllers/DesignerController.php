<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Preset;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

class DesignerController extends Controller
{
    public function show(Event $event): View
    {
        return view('designer', [
            'event' => $event,
            'data' => self::payload($event),
            'presets' => Preset::orderBy('name')->get(),
            'venues' => Venue::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Save the whole layout in one request: the venue's fixed features
     * (boundary, doors, power) and the event's tables for this venue.
     * Returns the fresh payload so the canvas can re-render with real database ids.
     */
    public function save(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'venue_id' => 'nullable|exists:venues,id',
            'event.name' => 'sometimes|string|max:255',
            'event.starts_at' => 'nullable|date',
            'event.ends_at' => 'nullable|date',
            'event.is_public' => 'boolean',
            'event.registration_opens_at' => 'nullable|date',
            'event.registration_closes_at' => 'nullable|date',
            'event.cancellation_deadline' => 'nullable|date',
            'venue.area' => 'nullable|array',
            'venue.doors' => 'array',
            'venue.doors.*.id' => 'nullable|integer',
            'venue.doors.*.label' => 'nullable|string',
            'venue.doors.*.type' => 'nullable|string',
            'venue.doors.*.x' => 'required|numeric',
            'venue.doors.*.y' => 'required|numeric',
            'venue.doors.*.width' => 'nullable|numeric',
            'venue.doors.*.rotation' => 'nullable|numeric',
            'venue.power_outlets' => 'array',
            'venue.power_outlets.*.id' => 'nullable|integer',
            'venue.power_outlets.*.label' => 'nullable|string',
            'venue.power_outlets.*.x' => 'required|numeric',
            'venue.power_outlets.*.y' => 'required|numeric',
            'venue.power_outlets.*.amperage' => 'nullable|integer',
            'venue.power_outlets.*.voltage' => 'nullable|integer',
            'venue.power_outlets.*.outlets' => 'nullable|integer',
            'tables' => 'array',
            'tables.*.id' => 'nullable|integer',
            'tables.*.label' => 'nullable|string',
            'tables.*.x' => 'required|numeric',
            'tables.*.y' => 'required|numeric',
            'tables.*.width' => 'nullable|numeric',
            'tables.*.height' => 'nullable|numeric',
            'tables.*.rotation' => 'nullable|numeric',
            'tables.*.shape' => 'nullable|in:rect,round',
            'tables.*.price' => 'nullable|numeric',
            'tables.*.status' => 'nullable|in:available,held,booked',
            'tables.*.has_power' => 'boolean',
        ]);

        // The venue is only committed to the event here, on save.
        if (! empty($validated['venue_id'])) {
            $event->update(['venue_id' => $validated['venue_id']]);
            $event->load('venue');
        }

        $venue = $event->venue;

        $venue->area = ! empty($validated['venue']['area'])
            ? Polygon::fromArray($validated['venue']['area'], 0)
            : null;
        $venue->save();

        $this->sync($venue->doors(), $validated['venue']['doors'] ?? [], [
            'label', 'type', 'x', 'y', 'width', 'rotation',
        ]);
        $this->sync($venue->powerOutlets(), $validated['venue']['power_outlets'] ?? [], [
            'label', 'x', 'y', 'amperage', 'voltage', 'outlets',
        ]);

        // Tables are scoped to this event + venue so each venue keeps its own layout.
        // Force venue_id on every row so newly created tables land in the right bucket.
        $tableRows = array_map(
            fn ($t) => array_merge($t, ['venue_id' => $venue->id]),
            $validated['tables'] ?? []
        );
        $this->sync(
            $event->tables()->where('venue_id', $venue->id),
            $tableRows,
            ['venue_id', 'label', 'x', 'y', 'width', 'height', 'rotation', 'shape', 'price', 'status', 'has_power']
        );

        if (! empty($validated['event'])) {
            $event->fill($validated['event'])->save();
        }

        return response()->json(self::payload($event->fresh()));
    }

    /**
     * Generic "replace this whole set" helper: update rows that arrived with an
     * id, create rows that didn't, and delete anything no longer present.
     */
    private function sync($relation, array $rows, array $fields): void
    {
        $keep = [];

        foreach ($rows as $row) {
            $attributes = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $row)) {
                    $attributes[$field] = $row[$field];
                }
            }

            $record = $relation->updateOrCreate(['id' => $row['id'] ?? null], $attributes);
            $keep[] = $record->id;
        }

        $relation->whereNotIn('id', $keep ?: [0])->delete();
    }

    public static function payload(Event $event): array
    {
        $event->loadMissing('venue');

        return self::payloadFor($event, $event->venue);
    }

    /**
     * Build the designer payload for a specific event+venue combination.
     * Tables are filtered to only those placed in this venue, so switching
     * venues shows each venue's own independent table layout.
     */
    public static function payloadFor(Event $event, Venue $venue): array
    {
        $venue->load(['doors', 'powerOutlets']);

        return [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'venue_id' => $venue->id, // the venue currently shown, not necessarily saved
                'is_public' => (bool) $event->is_public,
                'starts_at' => $event->starts_at?->format('Y-m-d\TH:i'),
                'ends_at' => $event->ends_at?->format('Y-m-d\TH:i'),
                'registration_opens_at' => $event->registration_opens_at?->format('Y-m-d\TH:i'),
                'registration_closes_at' => $event->registration_closes_at?->format('Y-m-d\TH:i'),
                'cancellation_deadline' => $event->cancellation_deadline?->format('Y-m-d\TH:i'),
            ],
            'venue' => [
                'id' => $venue->id,
                'name' => $venue->name,
                'area' => $venue->area, // serializes to GeoJSON automatically
                'doors' => $venue->doors,
                'power_outlets' => $venue->powerOutlets,
            ],
            'tables' => $event->tables()->where('venue_id', $venue->id)->get(),
        ];
    }
}
