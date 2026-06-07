<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VendorApproved;
use App\Mail\VendorRejected;
use App\Models\Vendor;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function index(): View
    {
        return view('admin.vendors.index', [
            'pending' => Vendor::with('user')->where('status', 'pending')->latest()->get(),
            'approved' => Vendor::with('user')->where('status', 'approved')->latest()->get(),
            'rejected' => Vendor::with('user')->where('status', 'rejected')->latest()->get(),
        ]);
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
