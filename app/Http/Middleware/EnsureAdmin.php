<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the /admin area. Admin access is a simple session flag set by
 * AdminSessionController after checking the plain-text credentials in
 * config.php (config('vendormap.admin')). This is intentionally separate
 * from the normal user/vendor login.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('is_admin') !== true) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
