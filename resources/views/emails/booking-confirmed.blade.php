<!DOCTYPE html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color:#1b2430; line-height:1.5;">
    <h2>Table confirmed</h2>
    <p>Hi {{ $vendor->contact_name ?: $vendor->business_name }},</p>
    <p>
        Good news — your table for <strong>{{ $event->name }}</strong> has been
        confirmed by the organiser.
    </p>
    <table cellpadding="6" style="border-collapse:collapse;">
        <tr><td><strong>Event</strong></td><td>{{ $event->name }}</td></tr>
        @if ($event->starts_at)
            <tr><td><strong>Date</strong></td><td>{{ $event->starts_at->format('D j M Y, g:ia') }}</td></tr>
        @endif
        <tr><td><strong>Table</strong></td><td>{{ $table->label ?: ('#' . $table->id) }}</td></tr>
    </table>
    <p>View your booking:
        <a href="{{ route('events.show', $event) }}">{{ route('events.show', $event) }}</a>
    </p>
</body>
</html>
