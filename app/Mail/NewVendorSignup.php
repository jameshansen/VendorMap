<?php

namespace App\Mail;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the site admin when a new vendor applies. */
class NewVendorSignup extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Vendor $vendor) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New vendor application: ' . $this->vendor->business_name);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vendor-signup', with: ['vendor' => $this->vendor]);
    }
}
