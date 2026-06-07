<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the current user is signed in and has an approved vendor profile.
 * Used to guard booking actions. Anonymous users are sent to login; signed-in
 * but unapproved vendors are bounced back with a message.
 */
class EnsureApprovedVendor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $vendor = $user->vendor;

        if (! $vendor || ! $vendor->isApproved()) {
            return redirect()->route('home')
                ->with('status', 'Your account is still pending approval. You can book once an admin approves it.');
        }

        return $next($request);
    }
}
