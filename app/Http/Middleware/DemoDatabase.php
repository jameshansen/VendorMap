<?php

namespace App\Http\Middleware;

use App\Support\DemoPool;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * When demo mode is on, route every request to the visitor's own pool database
 * (allocating/recycling a slot as needed) before any controller runs, and pin
 * their slot with a cookie. No-op when demo mode is off.
 */
class DemoDatabase
{
    public const COOKIE = 'demo_slot';

    public function __construct(private DemoPool $pool) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('vendormap.demo.enabled')) {
            return $next($request);
        }

        [$slot, $token, $needsReseed] = $this->pool->allocate($request->cookie(self::COOKIE));

        if ($needsReseed) {
            $this->pool->reseed($slot);   // also switches the connection
        } else {
            $this->pool->switchTo($slot);
        }

        $response = $next($request);

        // Pin the slot for a day (refreshed each request).
        Cookie::queue(Cookie::make(self::COOKIE, "{$slot}:{$token}", 60 * 24));

        return $response;
    }
}
