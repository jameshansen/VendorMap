<?php

namespace App\Mail;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to a vendor when an admin approves their account. */
class VendorApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Vendor $vendor) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your VendorMap account is approved');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vendor-approved', with: ['vendor' => $this->vendor]);
    }
}
