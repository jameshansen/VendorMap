<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\NewVendorSignup;
use App\Models\User;
use App\Support\Notify;
use App\Support\VendorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /** Is Google sign-in actually configured? */
    public static function enabled(): bool
    {
        return filled(config('services.google.client_id'));
    }

    public function redirect(): RedirectResponse
    {
        if (! self::enabled()) {
            return redirect()->route('login')->withErrors(['email' => 'Google sign-in is not configured.']);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! self::enabled()) {
            return redirect()->route('login');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors(['email' => 'Google sign-in failed. Please try again.']);
        }

        $email = $googleUser->getEmail();
        if (! $email) {
            return redirect()->route('login')->withErrors(['email' => 'Your Google account did not share an email.']);
        }

        // Find by google_id, then by email (link an existing email account).
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $email)->first();

        if ($user) {
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: $email,
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'role' => 'vendor',
                'password' => null,
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        // Google verifies the email, but we still need a vendor profile + approval.
        if (! $user->vendor) {
            return redirect()->route('register.complete');
        }

        return redirect()->route('home');
    }

    /** Profile completion for a freshly-created Google account (no vendor yet). */
    public function showComplete(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user->vendor) {
            return redirect()->route('home');
        }

        return view('auth.complete', ['user' => $user]);
    }

    public function storeComplete(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->vendor) {
            return redirect()->route('home');
        }

        $request->validate(array_merge(
            ['application_note' => 'nullable|string|max:1000'],
            VendorProfile::rules()
        ));

        $vendor = $user->vendor()->create(array_merge(
            VendorProfile::attributes($request),
            [
                'status' => 'pending',
                'email' => $user->email,
                'application_note' => $request->input('application_note'),
            ]
        ));

        // Keep the user's display name in step with the contact name they gave.
        $user->update(['name' => $vendor->contact_name]);

        Notify::mail(config('vendormap.smtp.admin_notify'), new NewVendorSignup($vendor));

        return redirect()->route('home')->with('status',
            'Thanks! Your account is pending approval — we\'ll email you when it\'s ready.');
    }
}
