<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VendorApproved;
use App\Mail\VendorRejected;
use App\Models\Event;
use App\Models\EventTable;
use App\Models\Vendor;
use App\Support\Notify;
use App\Support\VendorGuidance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function index(Request $request): View
    {
        $events = Event::orderBy('starts_at')->get(['id', 'name', 'starts_at']);

        // Vendor accounts are global but bookings belong to one event, so the
        // "has booked" column always names the event it refers to. Defaults to
        // the next event still to come, or the most recent one if none are.
        $event = $request->filled('event')
            ? $events->firstWhere('id', (int) $request->input('event'))
            : null;
        $event ??= $events->first(fn ($e) => $e->starts_at === null || $e->starts_at->isFuture())
            ?? $events->last();

        return view('admin.vendors.index', [
            'pending' => Vendor::with('user')->where('status', 'pending')->latest()->get(),
            'approved' => Vendor::with('user')->where('status', 'approved')->latest()->get(),
            'rejected' => Vendor::with('user')->where('status', 'rejected')->latest()->get(),
            'guidance' => VendorGuidance::summary(),
            'events' => $events,
            'event' => $event,
            'tablesByVendor' => $this->tablesByVendor($event),
        ]);
    }

    /** Tables booked or held at the given event, keyed by vendor id. */
    private function tablesByVendor(?Event $event)
    {
        if (! $event) {
            return collect();
        }

        return EventTable::where('event_id', $event->id)
            ->whereNotNull('vendor_id')
            ->orderBy('label')
            ->get(['vendor_id', 'label', 'status', 'paid'])
            ->groupBy('vendor_id');
    }

    public function approve(Request $request, Vendor $vendor): RedirectResponse
    {
        $vendor->update([
            'status' => 'approved',
            'approved_at' => now(),
            'admin_notes' => $request->input('admin_notes', $vendor->admin_notes),
        ]);

        Notify::mail($this->vendorEmail($vendor), new VendorApproved($vendor));

        return back()->with('status', "{$vendor->business_name} approved.");
    }

    /** One-click approval from the signup email — no admin session, signature is the auth. */
    public function approveViaLink(Vendor $vendor): View
    {
        $already = $vendor->status === 'approved';

        if (! $already) {
            $vendor->update(['status' => 'approved', 'approved_at' => now()]);
            Notify::mail($this->vendorEmail($vendor), new VendorApproved($vendor));
        }

        return view('admin.vendors.approved', ['vendor' => $vendor, 'already' => $already]);
    }

    public function reject(Request $request, Vendor $vendor): RedirectResponse
    {
        $vendor->update([
            'status' => 'rejected',
            'admin_notes' => $request->input('admin_notes', $vendor->admin_notes),
        ]);

        Notify::mail($this->vendorEmail($vendor), new VendorRejected($vendor));

        return back()->with('status', "{$vendor->business_name} rejected.");
    }

    private function vendorEmail(Vendor $vendor): ?string
    {
        return $vendor->email ?: $vendor->user?->email;
    }
}
