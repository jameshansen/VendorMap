@extends('layouts.app')

@section('title', 'Events')

@section('body')
    <main class="events-page">
        <header>
            <h1>Events</h1>
            <p>Pick an event to design its floor plan.</p>
        </header>

        @if ($events->isEmpty())
            <p class="empty">No events yet. Run <code>php artisan db:seed --class=DemoSeeder</code> to create the demo.</p>
        @else
            <ul class="event-list">
                @foreach ($events as $event)
                    <li>
                        <a href="{{ route('designer.show', $event) }}">
                            <span class="name">{{ $event->name }}</span>
                            <span class="meta">{{ $event->venue?->name }} · {{ $event->tables_count }} tables</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </main>
@endsection
