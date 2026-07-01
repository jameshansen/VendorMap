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

/** Sent to the vendor when the organiser confirms their previously-held table. */
class BookingConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public Vendor $vendor,
        public EventTable $table,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Table confirmed — {$this->event->name}");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-confirmed',
            with: [
                'event' => $this->event,
                'vendor' => $this->vendor,
                'table' => $this->table,
            ],
        );
    }
}
