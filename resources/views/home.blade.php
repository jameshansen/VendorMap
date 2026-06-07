@extends('layouts.app')

@section('title', 'Book a table')

@section('body')
@include('partials.site-header')

@php
    $vendor = auth()->user()?->vendor;
    $approved = $vendor?->isApproved();
@endphp

<main class="home-page">
    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    @auth
        @if ($vendor && ! $approved)
            <div class="banner pending">
                <strong>Your account is pending approval.</strong>
                You can browse events now and book as soon as an admin approves you — we'll email you.
            </div>
        @endif

        @if ($vendor)
            <section class="my-bookings">
                <h2>Your bookings</h2>
                @if ($myBookings->isEmpty())
                    <p class="muted">You haven't booked any tables yet — pick an event below to get started.</p>
                @else
                    <ul class="booking-list">
                        @foreach ($myBookings as $b)
                            <li>
                                <div class="bl-main">
                                    <strong>{{ $b->event?->name ?? 'Event' }}</strong>
                                    <span class="muted">Table {{ $b->label }}@if ($b->event?->starts_at) · {{ $b->event->starts_at->format('M j, Y') }}@endif</span>
                                </div>
                                <div class="bl-tags">
                                    <span class="badge badge-{{ $b->status }}">{{ $b->status }}</span>
                                    <span class="badge {{ $b->paid ? 'badge-paid' : 'badge-unpaid' }}">{{ $b->paid ? 'Paid' : 'Payment pending' }}</span>
                                    <span class="muted">${{ number_format((float) $b->price, 2) }}</span>
                                    @if ($b->event)
                                        <a class="muted-link" href="{{ route('events.show', $b->event) }}">View</a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif
    @endauth

    <header class="home-head">
        <h1>Upcoming events</h1>
        <p class="muted">Browse events and reserve your table.</p>
    </header>

    @if ($events->isEmpty())
        <p class="muted">No events are open for booking right now. Check back soon.</p>
    @else
        <ul class="event-cards">
            @foreach ($events as $event)
                @php $open = $event->registrationOpen(); @endphp
                <li class="event-card">
                    <div class="event-card-body">
                        <h2>{{ $event->name }}</h2>
                        <p class="event-where">{{ $event->venue?->name ?? 'Venue TBA' }}</p>
                        @if ($event->starts_at)
                            <p class="event-when">{{ $event->starts_at->format('l, M j Y · g:ia') }}</p>
                        @endif
                        <p class="event-avail">
                            <span class="dot-avail {{ $event->available_count > 0 ? 'on' : 'off' }}"></span>
                            {{ $event->available_count }} of {{ $event->total_count }} tables available
                        </p>
                    </div>
                    <div class="event-card-action">
                        @guest
                            <a class="btn-secondary" href="{{ route('login') }}">Sign in to book</a>
                        @else
                            @if (! $vendor)
                                <a class="btn-secondary" href="{{ route('register.complete') }}">Finish your profile</a>
                            @elseif (! $approved)
                                <span class="muted">Awaiting approval</span>
                            @elseif (! $open)
                                <span class="muted">Registration closed</span>
                            @elseif ($event->available_count < 1)
                                <span class="muted">Fully booked</span>
                            @else
                                <a class="btn-primary" href="{{ route('events.show', $event) }}">Book a table</a>
                            @endif
                        @endguest
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</main>
@endsection
