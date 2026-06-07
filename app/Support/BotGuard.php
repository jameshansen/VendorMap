<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Lightweight anti-bot checks for public forms (sign-up). Layers:
 *   1. Honeypot   — a hidden field humans never fill; bots usually do.
 *   2. Timing     — reject submissions that arrive implausibly fast.
 *   3. reCAPTCHA  — optional, only when keys are present in config.php.
 *
 * Returns null when the submission looks human, or a short reason string
 * when it should be rejected.
 */
class BotGuard
{
    /** Hidden honeypot input name shared by the form and this guard. */
    public const HONEYPOT = 'company_url';

    /** Session key holding when the form was rendered. */
    public const RENDERED_AT = 'form_rendered_at';

    /** Minimum seconds a real human takes to fill the form. */
    private const MIN_SECONDS = 3;

    public static function markRendered(Request $request): void
    {
        $request->session()->put(self::RENDERED_AT, time());
    }

    public static function check(Request $request): ?string
    {
        // 1. Honeypot must be empty.
        if (filled($request->input(self::HONEYPOT))) {
            return 'honeypot';
        }

        // 2. Submitted too quickly to be a human.
        $renderedAt = (int) $request->session()->pull(self::RENDERED_AT, 0);
        if ($renderedAt > 0 && (time() - $renderedAt) < self::MIN_SECONDS) {
            return 'too-fast';
        }

        // 3. Optional reCAPTCHA.
        $secret = (string) config('vendormap.recaptcha.secret_key');
        if ($secret !== '') {
            $token = (string) $request->input('g-recaptcha-response');
            if ($token === '' || ! self::verifyRecaptcha($secret, $token, $request->ip())) {
                return 'captcha';
            }
        }

        return null;
    }

    private static function verifyRecaptcha(string $secret, string $token, ?string $ip): bool
    {
        try {
            $resp = Http::asForm()->timeout(5)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            return (bool) ($resp->json('success') ?? false);
        } catch (\Throwable) {
            // If Google is unreachable, don't lock real users out.
            return true;
        }
    }
}
