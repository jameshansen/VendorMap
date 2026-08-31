<?php

namespace App\Mail;

use App\Models\Vendor;
use App\Support\VendorGuidance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

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
        return new Content(view: 'emails.vendor-signup', with: [
            'vendor' => $this->vendor,
            'approveUrl' => URL::signedRoute('vendors.approve.link', $this->vendor),
            // Admin-only recipient, so the AI guidance may be included here.
            'guidance' => VendorGuidance::summary(),
        ]);
    }
}
