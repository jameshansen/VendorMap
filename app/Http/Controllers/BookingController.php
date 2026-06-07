<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vendor-facing booking. Renders the event's committed-venue floor plan and
 * lets an approved vendor claim a table, honouring the per-event limit and the
 * auto-approve setting from config.php.
 */
class BookingController extends Controller
{
    public function show(Request $request, Event $event): View
    {
        abort_unless($event->is_public, 404);
        abort_unless($event->venue_id, 404, 'This event has no venue yet.');

        return view('booking.show', [
            'event' => $event,
            'state' => $this->state($event, $request),
        ]);
    }

    public function store(Request $request, Event $event): JsonResponse
    {
        abort_unless($event->is_public && $event->venue_id, 404);

        if (! $event->registrationOpen()) {
            return response()->json(['error' => 'Registration is closed for this event.'], 422);
        }

        $vendor = $request->user()->vendor;
        $data = $request->validate(['table_id' => 'required|integer']);

        $table = $event->tables()
            ->where('venue_id', $event->venue_id)
            ->find($data['table_id']);

        if (! $table) {
            return response()->json(['error' => 'That table is not part of this event.'], 422);
        }

        if ($table->status !== 'available') {
            return response()->json(['error' => 'Sorry, that table was just taken.'], 422);
        }

        $limit = (int) config('vendormap.booking.tables_per_vendor', 1);
        if ($this->vendorTableCount($event, $vendor->id) >= $limit) {
            return response()->json([
                'error' => $limit === 1
                    ? 'You can only book one table for this event.'
                    : "You can book at most {$limit} tables for this event.",
            ], 422);
        }

        $autoApprove = (bool) config('vendormap.booking.auto_approve_booking', true);

        $table->update([
            'vendor_id' => $vendor->id,
            'status' => $autoApprove ? 'booked' : 'held',
            'booked_at' => now(),
        ]);

        return response()->json([
            'message' => $autoApprove
                ? 'Table booked! The organiser will send payment instructions to follow.'
                : 'Table requested — the organiser will confirm it and send payment instructions to follow.',
            'state' => $this->state($event, $request),
        ]);
    }

    public function destroy(Request $request, Event $event, EventTable $table): JsonResponse
    {
        abort_unless($event->is_public && $event->venue_id, 404);

        $vendor = $request->user()->vendor;

        if ($table->event_id !== $event->id || $table->vendor_id !== $vendor->id) {
            return response()->json(['error' => 'That booking is not yours.'], 422);
        }

        // Booked tables can only be released before the cancellation deadline.
        if ($table->status === 'booked'
            && $event->cancellation_deadline
            && now()->gt($event->cancellation_deadline)) {
            return response()->json(['error' => 'The cancellation deadline has passed.'], 422);
        }

        $table->update(['vendor_id' => null, 'status' => 'available', 'booked_at' => null, 'paid' => false, 'paid_at' => null]);

        return response()->json([
            'message' => 'Booking released.',
            'state' => $this->state($event, $request),
        ]);
    }

    /** Booking payload for the front-end (floor plan + context). */
    private function state(Event $event, Request $request): array
    {
        $vendor = $request->user()?->vendor;

        return [
            'data' => DesignerController::payload($event),
            'vendorId' => $vendor?->id,
            'perVendor' => (int) config('vendormap.booking.tables_per_vendor', 1),
            'autoApprove' => (bool) config('vendormap.booking.auto_approve_booking', true),
            'myCount' => $vendor ? $this->vendorTableCount($event, $vendor->id) : 0,
            'registrationOpen' => $event->registrationOpen(),
        ];
    }

    private function vendorTableCount(Event $event, int $vendorId): int
    {
        return $event->tables()
            ->where('venue_id', $event->venue_id)
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['held', 'booked'])
            ->count();
    }
}
