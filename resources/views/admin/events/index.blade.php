@extends('layouts.admin')

@section('title', 'Events')

@section('content')
    <div class="page-head">
        <h1>Events</h1>
        <a class="btn-primary" href="{{ route('admin.events.create') }}">New event</a>
    </div>

    @if ($events->isEmpty())
        <p class="muted">No events yet. Create one to get started.</p>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th><th>Venue</th><th>Status</th><th>Public</th>
                    <th>Starts</th><th>Tables</th><th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($events as $event)
                    <tr>
                        <td><strong>{{ $event->name }}</strong></td>
                        <td>{{ $event->venue?->name ?? '—' }}</td>
                        <td><span class="badge badge-{{ $event->status }}">{{ $event->status }}</span></td>
                        <td>{{ $event->is_public ? 'Yes' : 'No' }}</td>
                        <td>{{ $event->starts_at?->format('M j, Y g:ia') ?? '—' }}</td>
                        <td>{{ $event->tables_count }}</td>
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('admin.designer.show', $event) }}">Designer</a>
                                <a href="{{ route('admin.bookings.index', $event) }}">Bookings</a>
                                <a href="{{ route('admin.events.edit', $event) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.events.destroy', $event) }}"
                                      onsubmit="return confirm('Delete this event and its layout?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="link-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
