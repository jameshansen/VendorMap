<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin login backed by the plain-text credentials in config.php
 * (config('vendormap.admin')). On success we set a session flag that
 * EnsureAdmin checks. No database user is involved.
 */
class AdminSessionController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (request()->session()->get('is_admin') === true) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $expected = config('vendormap.admin');

        // Constant-time comparison for the password to avoid timing leaks.
        $ok = hash_equals((string) ($expected['username'] ?? ''), $credentials['username'])
            & (int) hash_equals((string) ($expected['password'] ?? ''), $credentials['password']);

        if (! $ok) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Those admin credentials are not valid.']);
        }

        $request->session()->regenerate();
        $request->session()->put('is_admin', true);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('is_admin');

        return redirect()->route('admin.login');
    }
}
