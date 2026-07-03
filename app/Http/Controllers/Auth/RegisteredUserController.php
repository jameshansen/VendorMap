<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\NewVendorSignup;
use App\Mail\VendorPending;
use App\Models\User;
use App\Support\BotGuard;
use App\Support\Notify;
use App\Support\VendorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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

        $validator = Validator::make($request->all(), array_merge([
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'application_note' => 'nullable|string|max:1000',
            'verify_photo' => 'nullable|image|max:5120',
        ], VendorProfile::rules()));
        $validator->after(fn ($v) => VendorProfile::requireVerification($v, $request));
        $validated = $validator->validate();

        // A verification photo (signup only) is stored as a file and its URL is
        // appended to the note, so it lives in the one application_note field.
        $note = $validated['application_note'] ?? null;
        if ($request->hasFile('verify_photo')) {
            $file = $request->file('verify_photo');
            $name = date('Ymd_His') . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            File::ensureDirectoryExists(public_path('vphotos'));
            $file->move(public_path('vphotos'), $name);
            $note = trim(($note ? $note . "\n" : '') . URL::to('vphotos/' . $name));
        }

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
                'application_note' => $note,
            ]
        ));

        Notify::mail(config('vendormap.smtp.admin_notify'), new NewVendorSignup($vendor));
        Notify::mail($vendor->email, new VendorPending($vendor));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('status',
            'Thanks for signing up! Your account is pending approval — we\'ll email you when it\'s ready.');
    }
}
