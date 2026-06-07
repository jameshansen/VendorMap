<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Best-effort email helper. Sending must never break the surrounding request
 * (e.g. a signup), so failures and missing recipients are logged rather than
 * thrown. SMTP is configured from the root config.php via ConfigBridge.
 */
class Notify
{
    public static function mail(?string $to, Mailable $mailable): void
    {
        $to = trim((string) $to);

        if ($to === '') {
            Log::info('Notify: skipped email (no recipient configured)', [
                'mailable' => $mailable::class,
            ]);

            return;
        }

        try {
            Mail::to($to)->send($mailable);
        } catch (\Throwable $e) {
            Log::warning('Notify: email send failed', [
                'mailable' => $mailable::class,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
