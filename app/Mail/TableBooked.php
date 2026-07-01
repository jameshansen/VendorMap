<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventTable;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when a vendor claims a table. The same booking notifies two audiences:
 *   - the admin (forAdmin = true), so they know a table was taken;
 *   - the vendor (forAdmin = false), as their booking confirmation.
 *
 * $held reflects the auto-approve setting: a held table still needs the
 * organiser to confirm it, so the wording differs from a straight booking.
 */
class TableBooked extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public Vendor $vendor,
        public EventTable $table,
        public bool $held = false,
        public bool $forAdmin = false,
    ) {}

    public function envelope(): Envelope
    {
        $business = $this->vendor->business_name;

        if ($this->forAdmin) {
            $verb = $this->held ? 'requested' : 'booked';

            return new Envelope(
                subject: "Table {$verb}: {$business} — {$this->event->name}",
            );
        }

        return new Envelope(
            subject: ($this->held ? 'Table requested' : 'Table booked')
                . " — {$this->event->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->forAdmin ? 'emails.table-booked-admin' : 'emails.table-booked-vendor',
            with: [
                'event' => $this->event,
                'vendor' => $this->vendor,
                'table' => $this->table,
                'held' => $this->held,
            ],
        );
    }
}
