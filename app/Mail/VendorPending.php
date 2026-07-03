<?php

namespace App\Mail;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to a vendor right after they sign up, confirming their application is pending. */
class VendorPending extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Vendor $vendor) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your VendorMap application is being reviewed');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vendor-pending', with: ['vendor' => $this->vendor]);
    }
}
