<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class EventController extends Controller
{
    /** Public booking landing: events open to the public, with availability. */
    public function index(): View
    {
        $events = Event::with('venue:id,name')
            ->where('is_public', true)
            ->withCount([
                // Tables for the event's committed venue that are still free.
                'tables as available_count' => fn ($q) => $q
                    ->where('status', 'available')
                    ->whereColumn('venue_id', 'events.venue_id'),
                'tables as total_count' => fn ($q) => $q
                    ->whereColumn('venue_id', 'events.venue_id'),
            ])
            ->orderByRaw('starts_at is null, starts_at')
            ->get();

        // The signed-in vendor's own bookings, for the "Your bookings" panel.
        $vendor = auth()->user()?->vendor;
        $myBookings = $vendor
            ? $vendor->tables()->with('event:id,name,slug,starts_at')->latest('booked_at')->get()
            : collect();

        return view('home', ['events' => $events, 'myBookings' => $myBookings]);
    }
}
