<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::with('venue:id,name')
            ->withCount('tables')
            ->latest()
            ->get();

        return view('admin.events.index', ['events' => $events]);
    }

    public function create(): View
    {
        return view('admin.events.create', [
            'event' => new Event(['status' => 'draft', 'is_public' => false]),
            'venues' => Venue::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data = $this->ensureVenue($data);

        $event = Event::create($data);

        return redirect()->route('admin.events.index')
            ->with('status', "Event \"{$event->name}\" created.");
    }

    public function edit(Event $event): View
    {
        return view('admin.events.edit', [
            'event' => $event,
            'venues' => Venue::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $data = $this->ensureVenue($this->validated($request));
        $event->update($data);

        return redirect()->route('admin.events.index')
            ->with('status', "Event \"{$event->name}\" updated.");
    }

    public function destroy(Event $event): RedirectResponse
    {
        $name = $event->name;
        $event->delete();

        return redirect()->route('admin.events.index')
            ->with('status', "Event \"{$name}\" deleted.");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'venue_id' => 'nullable|exists:venues,id',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published,closed',
            'is_public' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'registration_opens_at' => 'nullable|date',
            'registration_closes_at' => 'nullable|date|after_or_equal:registration_opens_at',
            'cancellation_deadline' => 'nullable|date',
        ]);

        $data['is_public'] = $request->boolean('is_public');

        return $data;
    }

    /**
     * Events require a venue (the column is NOT NULL). When the admin leaves the
     * venue as "+ New venue", spin up a blank default hall (20m x 14m, in cm) and
     * attach it — they can then shape it in the designer, just like the designer's
     * own "new venue" flow.
     */
    private function ensureVenue(array $data): array
    {
        if (! empty($data['venue_id'])) {
            return $data;
        }

        $name = ($data['name'] ?? 'New event') . ' venue';

        $venue = Venue::create([
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(5)),
            'area' => new Polygon([
                new LineString([
                    new Point(0, 0, 0),
                    new Point(0, 2000, 0),
                    new Point(1400, 2000, 0),
                    new Point(1400, 0, 0),
                    new Point(0, 0, 0),
                ]),
            ], 0),
        ]);

        $data['venue_id'] = $venue->id;

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'event';
        $slug = $base;

        while (Event::where('slug', $slug)->exists()) {
            $slug = $base . '-' . Str::lower(Str::random(5));
        }

        return $slug;
    }
}
