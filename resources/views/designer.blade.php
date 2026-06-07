@extends('layouts.app')

@section('title', 'Designer · ' . $event->name)

@section('head')
    @vite(['resources/js/designer.js'])
@endsection

@section('body')
<div class="designer">
    <header class="toolbar">
        <div class="brand">
            <span class="dot"></span>
            <select id="venue-select" class="venue-select" title="Venue for this event">
                @foreach ($venues as $v)
                    <option value="{{ $v->id }}" @selected($v->id === $data['event']['venue_id'])>{{ $v->name }}</option>
                @endforeach
                <option value="__new__">+ New venue…</option>
            </select>
            <button id="dup-venue" class="ghost" title="Duplicate this venue">Duplicate</button>
        </div>

        <div class="tools">
            <button data-tool="details" class="active">
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                Details
            </button>
            <button data-tool="select">
                <svg class="ico" viewBox="0 0 24 24" fill="currentColor"><path d="M5 3l6.5 16 2.2-6.3 6.3-2.2z"/></svg>
                Select
            </button>
            <button data-tool="boundary">
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M12 3l8 6-3 10H7L4 9z"/></svg>
                Boundary
            </button>
            <button data-tool="door">
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 21V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v17"/><path d="M4 21h16"/><circle cx="14.5" cy="12" r="1" fill="currentColor" stroke="none"/></svg>
                Door
            </button>
            <button data-tool="power">
                <svg class="ico" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L4 14h6l-1 8 9-12h-6z"/></svg>
                Power
            </button>
            <button data-tool="table">
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="7" width="18" height="4" rx="1"/><path d="M5 11v7M19 11v7"/></svg>
                Table
            </button>
        </div>

        <div class="right">
            <a class="back" href="{{ route('events.index') }}">All events</a>
            <span id="status" class="status">Loading…</span>
            <button id="save" class="save">Save Event and Layout</button>
        </div>
    </header>

    <div class="body">
        <div id="stage" class="stage"></div>

        <aside class="panel">
            <p id="hint" class="hint">
                Select something to edit it, or pick a tool above to add features.
                Scroll to zoom, drag empty space to pan. Right-click an object to save it as a preset.
            </p>

            {{-- Event details (shown for the Details tool) --}}
            <div id="panel-event" class="fields" hidden>
                <h3>Event details</h3>
                <label>Name <input id="e_name" type="text"></label>
                <label>Starts <input id="e_starts" type="datetime-local"></label>
                <label>Ends <input id="e_ends" type="datetime-local"></label>
                <label class="check"><input id="e_public" type="checkbox"> Show on the website</label>
                <label>Registration opens <input id="e_reg_open" type="datetime-local"></label>
                <label>Registration closes <input id="e_reg_close" type="datetime-local"></label>
                <label>Cancellation allowed until <input id="e_cancel" type="datetime-local"></label>
                <p class="hint">Saved with “Save Event and Layout”.</p>
            </div>

            {{-- Preset palette (shown for door / power / table tools) --}}
            <div id="palette" class="palette" hidden>
                <h3 id="palette-title">Presets</h3>
                <p class="hint">Click to pick the default, or drag one onto the floor.</p>
                <div id="palette-list" class="palette-list"></div>
            </div>

            {{-- Table --}}
            <div id="panel-table" class="fields" hidden>
                <h3>Table</h3>
                <label>Label <input id="t_label" type="text"></label>
                <label>Price <input id="t_price" type="number" step="0.01"></label>
                <div class="row">
                    <label>Width (cm) <input id="t_width" type="number"></label>
                    <label>Height (cm) <input id="t_height" type="number"></label>
                </div>
                <label>Rotation° <input id="t_rotation" type="number"></label>
                <label>Shape
                    <select id="t_shape">
                        <option value="rect">Rectangle</option>
                        <option value="round">Round</option>
                    </select>
                </label>
                <label>Status
                    <select id="t_status">
                        <option value="available">Available</option>
                        <option value="held">Held</option>
                        <option value="booked">Booked</option>
                    </select>
                </label>
                <button class="danger" data-delete>Delete table</button>
            </div>

            {{-- Door --}}
            <div id="panel-door" class="fields" hidden>
                <h3>Door</h3>
                <label>Label <input id="d_label" type="text"></label>
                <label>Type
                    <select id="d_type">
                        <option value="entrance">Entrance</option>
                        <option value="exit">Exit</option>
                        <option value="emergency">Emergency</option>
                        <option value="loading">Loading</option>
                    </select>
                </label>
                <div class="row">
                    <label>Width (cm) <input id="d_width" type="number"></label>
                    <label>Rotation° <input id="d_rotation" type="number"></label>
                </div>
                <button class="danger" data-delete>Delete door</button>
            </div>

            {{-- Power --}}
            <div id="panel-power" class="fields" hidden>
                <h3>Power outlet</h3>
                <label>Label <input id="p_label" type="text"></label>
                <div class="row">
                    <label>Amps <input id="p_amperage" type="number"></label>
                    <label>Volts <input id="p_voltage" type="number"></label>
                </div>
                <label>Outlets <input id="p_outlets" type="number"></label>
                <button class="danger" data-delete>Delete outlet</button>
            </div>

            {{-- Boundary corner --}}
            <div id="panel-vertex" class="fields" hidden>
                <h3>Wall corner</h3>
                <p class="hint">Drag the corner on the canvas to reshape the room.</p>
                <button class="danger" data-delete>Delete corner</button>
            </div>
        </aside>
    </div>
</div>

{{-- Right-click menu --}}
<div id="ctxmenu" class="ctxmenu" hidden></div>

<script>
    window.__DESIGNER__ = {
        data: @json($data),
        presets: @json($presets),
        saveUrl: "{{ route('designer.save', $event) }}",
        presetsUrl: "{{ url('/presets') }}",
        venuePreviewBase: "{{ url('/events/' . $event->id . '/venue') }}",
        venueNewUrl: "{{ route('venue.create', $event) }}",
        venueDuplicateUrl: "{{ route('venue.duplicate', $event) }}",
        csrf: "{{ csrf_token() }}",
    };
</script>
@endsection
