@extends('layouts.app')

@section('title', 'Book · ' . $event->name)

@section('head')
    @vite(['resources/js/booking.js'])
@endsection

@section('body')
@include('partials.site-header')

<div class="booking-body">
    <div id="stage" class="stage"></div>

    <aside class="panel booking-panel">
        <div class="panel-head">
            <a class="muted-link" href="{{ route('home') }}">← All events</a>
            <h2>{{ $event->name }}</h2>
            <p class="muted">
                {{ $event->venue?->name }}
                @if ($event->starts_at) · {{ $event->starts_at->format('l, M j Y · g:ia') }} @endif
            </p>
            <button id="unit-toggle" class="btn-link" type="button" title="Switch measurement units">Show in ft/in</button>
        </div>

        {{-- Prompt shown until a table is selected --}}
        <p id="book-hint" class="hint">Select a table on the map to see its details and book it.</p>

        {{-- Selected table details --}}
        <div id="book-detail" hidden>
            <h3 id="bd-title">Table</h3>
            <dl class="bd-grid">
                <div><dt>Price</dt><dd id="bd-price">—</dd></div>
                <div><dt>Size</dt><dd id="bd-size">—</dd></div>
                <div><dt>Power</dt><dd id="bd-power">—</dd></div>
                <div><dt>Status</dt><dd id="bd-status">—</dd></div>
            </dl>

            <button id="book-btn" class="btn-primary" type="button" hidden>Book table →</button>
            <button id="release-btn" class="danger" type="button" hidden>Release booking</button>
            <p id="book-msg" class="muted"></p>
        </div>

        <div class="booking-legend stacked">
            <span><i class="swatch av"></i> Available</span>
            <span><i class="swatch mine"></i> Yours</span>
            <span><i class="swatch held"></i> Held</span>
            <span><i class="swatch booked"></i> Taken</span>
            <span><i class="swatch power">⚡</i> Has power</span>
        </div>

        <p class="muted small">No payment is taken here — the organiser will send payment
            instructions to follow after you book.</p>
        <p id="status" class="status"></p>
    </aside>
</div>

<script>
    window.__BOOKING__ = {
        state: @json($state),
        vendorId: @json($state['vendorId']),
        bookUrl: "{{ route('events.book', $event) }}",
        releaseBase: "{{ url('/events/' . $event->id . '/bookings') }}",
        homeUrl: "{{ route('home') }}",
        profileUrl: "{{ route('profile.update') }}",
        csrf: "{{ csrf_token() }}",
    };
</script>
@endsection
