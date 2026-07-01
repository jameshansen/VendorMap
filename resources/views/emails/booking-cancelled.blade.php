<!DOCTYPE html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color:#1b2430; line-height:1.5;">
    <h2>Table cancelled</h2>
    <p>
        <strong>{{ $vendor->business_name }}</strong>
        has cancelled their table for <strong>{{ $event->name }}</strong>.
        The table is now available again.
    </p>
    <table cellpadding="6" style="border-collapse:collapse;">
        <tr><td><strong>Event</strong></td><td>{{ $event->name }}</td></tr>
        @if ($event->starts_at)
            <tr><td><strong>Date</strong></td><td>{{ $event->starts_at->format('D j M Y, g:ia') }}</td></tr>
        @endif
        <tr><td><strong>Table</strong></td><td>{{ $table->label ?: ('#' . $table->id) }}</td></tr>
        <tr><td><strong>Business</strong></td><td>{{ $vendor->business_name }}</td></tr>
        <tr><td><strong>Contact</strong></td><td>{{ $vendor->contact_name }}</td></tr>
        <tr><td><strong>Email</strong></td><td>{{ $vendor->email }}</td></tr>
        <tr><td><strong>Phone</strong></td><td>{{ $vendor->phone }}</td></tr>
    </table>
    <p>Manage bookings for this event:
        <a href="{{ route('admin.bookings.index', $event) }}">{{ route('admin.bookings.index', $event) }}</a>
    </p>
</body>
</html>
