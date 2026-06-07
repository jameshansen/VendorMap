<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\NewVendorSignup;
use App\Models\User;
use App\Support\BotGuard;
use App\Support\Notify;
use App\Support\VendorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        BotGuard::markRendered($request);

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        // Anti-bot gate first — bounce silently without leaking which check tripped.
        if (BotGuard::check($request) !== null) {
            return back()
                ->withInput($request->except('password', 'password_confirmation', BotGuard::HONEYPOT))
                ->withErrors(['email' => 'We could not verify your submission. Please try again.']);
        }

        $validated = $request->validate(array_merge([
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'application_note' => 'nullable|string|max:1000',
        ], VendorProfile::rules()));

        $user = User::create([
            'name' => $validated['contact_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'vendor',
        ]);

        $vendor = $user->vendor()->create(array_merge(
            VendorProfile::attributes($request),
            [
                'status' => 'pending',
                'email' => $validated['email'],
                'application_note' => $validated['application_note'] ?? null,
            ]
        ));

        Notify::mail(config('vendormap.smtp.admin_notify'), new NewVendorSignup($vendor));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('status',
            'Thanks for signing up! Your account is pending approval — we\'ll email you when it\'s ready.');
    }
}
