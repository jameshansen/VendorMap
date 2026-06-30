<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

class VenueController extends Controller
{
    /**
     * Preview an existing venue's layout for this event. This does NOT change
     * the event's venue; that only happens when the designer is saved.
     */
    public function preview(Event $event, Venue $venue): JsonResponse
    {
        return response()->json($this->state($event, $venue));
    }

    /** Create an empty rectangular venue (width x height, in cm) and preview it. */
    public function create(Request $request, Event $event): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'width' => 'required|numeric|min:50',
            'height' => 'required|numeric|min:50',
        ]);

        $venue = Venue::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . Str::lower(Str::random(5)),
            'area' => $this->rectangle($data['width'], $data['height']),
        ]);

        return response()->json($this->state($event, $venue));
    }

    /** Rename a venue (slug is left untouched so any public URLs keep working). */
    public function rename(Request $request, Event $event, Venue $venue): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        $venue->update(['name' => $data['name']]);

        return response()->json($this->state($event, $venue));
    }

    /** Copy a venue (outline, doors, power) into a new one and preview it. */
    public function duplicate(Request $request, Event $event): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'source_venue_id' => 'required|exists:venues,id',
        ]);

        $old = Venue::with(['doors', 'powerOutlets'])->findOrFail($data['source_venue_id']);

        $venue = Venue::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . Str::lower(Str::random(5)),
            'area' => $old->area,
        ]);

        foreach ($old->doors as $door) {
            $venue->doors()->create($door->only(['label', 'type', 'x', 'y', 'width', 'rotation']));
        }
        foreach ($old->powerOutlets as $outlet) {
            $venue->powerOutlets()->create($outlet->only(['label', 'x', 'y', 'amperage', 'voltage', 'outlets']));
        }

        // Copy the source venue's table layout for this event into the new venue
        // so the duplicate starts as a true copy of the layout, not a blank slate.
        $sourceTables = $event->tables()->where('venue_id', $old->id)->get();
        foreach ($sourceTables as $table) {
            $event->tables()->create(
                $table->only(['label', 'x', 'y', 'width', 'height', 'rotation', 'shape', 'price', 'status'])
                + ['venue_id' => $venue->id]
            );
        }

        return response()->json($this->state($event, $venue));
    }

    /** Designer payload for an event shown against a specific venue, plus the venue list. */
    private function state(Event $event, Venue $venue): array
    {
        return [
            'data' => DesignerController::payloadFor($event, $venue),
            'venues' => Venue::orderBy('name')->get(['id', 'name']),
        ];
    }

    /** Build a rectangle in the local cm coordinate space (SRID 0). */
    private function rectangle(float $w, float $h): Polygon
    {
        return new Polygon([
            new LineString([
                new Point(0, 0, 0),
                new Point(0, $w, 0),
                new Point($h, $w, 0),
                new Point($h, 0, 0),
                new Point(0, 0, 0),
            ]),
        ], 0);
    }
}
