<?php

namespace App\Http\Controllers;

use App\Support\VendorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $vendor = $user->vendor;

        if (! $vendor) {
            return redirect()->route('register.complete');
        }

        $bookings = $vendor->tables()
            ->with('event:id,name,slug,starts_at')
            ->get();

        return view('profile.edit', compact('user', 'vendor', 'bookings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $vendor = $user->vendor;

        if (! $vendor) {
            return redirect()->route('register.complete');
        }

        $request->validate(VendorProfile::rules());

        $vendor->update(VendorProfile::attributes($request));
        $user->update(['name' => $vendor->contact_name]);

        return back()->with('status', 'Profile updated.');
    }
}
