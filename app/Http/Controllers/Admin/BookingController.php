<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingConfirmed;
use App\Models\Event;
use App\Models\EventTable;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin view of the bookings for an event, with offline payment tracking
 * (mark paid / unpaid). Payment itself happens outside the app.
 */
class BookingController extends Controller
{
    public function index(Event $event): View
    {
        $bookings = $event->tables()
            ->whereNotNull('vendor_id')
            ->with('vendor:id,business_name,contact_name,email')
            ->orderBy('label')
            ->get();

        return view('admin.bookings.index', [
            'event' => $event,
            'bookings' => $bookings,
        ]);
    }

    public function confirm(Event $event, EventTable $table): RedirectResponse
    {
        $this->guard($event, $table);

        if ($table->status === 'held') {
            $table->update([
                'status' => 'booked',
                'booked_at' => $table->booked_at ?? now(),
            ]);

            // Let the vendor know their held table is now confirmed. Best-effort:
            // Notify logs (never throws) so mail issues can't fail the confirm.
            if ($vendor = $table->vendor) {
                Notify::mail(
                    $vendor->email ?: $vendor->user?->email,
                    new BookingConfirmed($event, $vendor, $table),
                );
            }

            return back()->with('status', "Table {$table->label} confirmed.");
        }

        return back()->with('status', "Table {$table->label} is not awaiting confirmation.");
    }

    public function markPaid(Event $event, EventTable $table): RedirectResponse
    {
        $this->guard($event, $table);
        $table->update(['paid' => true, 'paid_at' => now()]);

        return back()->with('status', "Table {$table->label} marked paid.");
    }

    public function markUnpaid(Event $event, EventTable $table): RedirectResponse
    {
        $this->guard($event, $table);
        $table->update(['paid' => false, 'paid_at' => null]);

        return back()->with('status', "Table {$table->label} marked unpaid.");
    }

    private function guard(Event $event, EventTable $table): void
    {
        abort_unless($table->event_id === $event->id, 404);
    }
}
