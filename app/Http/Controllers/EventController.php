<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::with('venue:id,name')
            ->withCount('tables')
            ->latest()
            ->get();

        return view('events.index', ['events' => $events]);
    }
}
