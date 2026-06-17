<?php

namespace App\Http\Controllers;

use App\Support\VendorProfile;
use Illuminate\Http\JsonResponse;
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

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $vendor = $user->vendor;

        if (! $vendor) {
            return $request->expectsJson()
                ? response()->json(['error' => 'No vendor profile.'], 422)
                : redirect()->route('register.complete');
        }

        $request->validate(VendorProfile::rules());

        $vendor->update(VendorProfile::attributes($request));
        $user->update(['name' => $vendor->contact_name]);

        // The booking wizard saves the profile over AJAX and expects JSON.
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Profile updated.',
                'vendor' => $vendor->only([
                    'business_name', 'contact_name', 'phone', 'address',
                    'website', 'socials', 'categories',
                ]),
            ]);
        }

        return back()->with('status', 'Profile updated.');
    }
}
