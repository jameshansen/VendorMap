@extends('layouts.admin')

@section('title', 'Bookings · ' . $event->name)

@section('content')
    <div class="page-head">
        <h1>Bookings — {{ $event->name }}</h1>
        <a class="btn-link" href="{{ route('admin.events.index') }}">← All events</a>
    </div>

    <p class="muted">Payment is handled offline (invoice / on the day). Use “Mark paid” to record
        when a vendor has settled up.</p>

    @if ($bookings->isEmpty())
        <p class="muted">No tables booked yet.</p>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>Table</th><th>Vendor</th><th>Contact</th><th>Price</th>
                    <th>Status</th><th>Payment</th><th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bookings as $t)
                    <tr>
                        <td><strong>{{ $t->label }}</strong></td>
                        <td>{{ $t->vendor?->business_name ?? '—' }}</td>
                        <td class="muted">{{ $t->vendor?->email ?? '' }}</td>
                        <td>${{ number_format((float) $t->price, 2) }}</td>
                        <td><span class="badge badge-{{ $t->status }}">{{ $t->status }}</span></td>
                        <td>
                            <span class="badge {{ $t->paid ? 'badge-paid' : 'badge-unpaid' }}">
                                {{ $t->paid ? 'Paid' : 'Unpaid' }}
                            </span>
                            @if ($t->paid && $t->paid_at)
                                <span class="muted">{{ $t->paid_at->format('M j') }}</span>
                            @endif
                        </td>
                        <td class="row-actions">
                            @if ($t->status === 'held')
                                <form method="POST" action="{{ route('admin.bookings.confirm', [$event, $t]) }}">
                                    @csrf <button type="submit" class="btn-primary sm">Confirm</button>
                                </form>
                            @endif
                            @if ($t->paid)
                                <form method="POST" action="{{ route('admin.bookings.unpaid', [$event, $t]) }}">
                                    @csrf <button type="submit" class="link-muted">Mark unpaid</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.bookings.paid', [$event, $t]) }}">
                                    @csrf <button type="submit" class="link-muted">Mark paid</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
