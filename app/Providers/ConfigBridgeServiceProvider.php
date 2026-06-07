<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Bridges the site owner's root-level config.php into Laravel's own config.
 *
 * The site owner edits a single, well-commented config.php at the project root
 * (copied from config.php.example). This provider reads it once at boot and
 * pushes the values into the places the framework and packages expect:
 *   - mail.* so SMTP "just works" without touching .env
 *   - services.google so Laravel Socialite can read the OAuth keys
 *   - vendormap.* a namespace for our own settings (admin creds, booking rules)
 *
 * If config.php is missing, sensible defaults are used and the app still boots.
 */
class ConfigBridgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $file = base_path('config.php');
        $cfg = is_file($file) ? (array) require $file : [];

        $this->bridgeVendorMap($cfg);
        $this->bridgeMail($cfg['smtp'] ?? []);
        $this->bridgeGoogle($cfg['google_oauth'] ?? []);
        $this->bridgeDemo($cfg['demo'] ?? []);
    }

    /**
     * In demo mode the default database connection is swapped per-request to a
     * throwaway pool slot, so sessions/cache/queue must NOT live in the database
     * (they'd reset whenever a slot is recycled). Force them to filesystem.
     */
    private function bridgeDemo(array $demo): void
    {
        config([
            'vendormap.demo' => [
                'enabled'   => (bool) ($demo['enabled'] ?? false),
                'pool_size' => max(1, (int) ($demo['pool_size'] ?? 25)),
                'db_prefix' => $demo['db_prefix'] ?? 'vendormap_demo_',
            ],
        ]);

        if (config('vendormap.demo.enabled')) {
            config([
                'session.driver' => 'file',
                'cache.default'  => 'file',
                'queue.default'  => 'sync',
            ]);
        }
    }

    /** Expose everything under config('vendormap.*') for the rest of the app. */
    private function bridgeVendorMap(array $cfg): void
    {
        config([
            'vendormap.name' => $cfg['app_name'] ?? 'VendorMap',
            'vendormap.admin' => [
                'username' => $cfg['admin']['username'] ?? 'admin',
                'password' => $cfg['admin']['password'] ?? 'change-me',
            ],
            'vendormap.booking' => [
                'tables_per_vendor'    => (int) ($cfg['booking']['tables_per_vendor'] ?? 1),
                'auto_approve_booking' => (bool) ($cfg['booking']['auto_approve_booking'] ?? true),
            ],
            'vendormap.recaptcha' => [
                'site_key'   => $cfg['recaptcha']['site_key'] ?? '',
                'secret_key' => $cfg['recaptcha']['secret_key'] ?? '',
            ],
            'vendormap.smtp.admin_notify' => $cfg['smtp']['admin_notify'] ?? '',
        ]);
    }

    /** Point Laravel's mailer at the configured SMTP server (or log if none). */
    private function bridgeMail(array $smtp): void
    {
        $host = $smtp['host'] ?? '';

        // From address applies regardless of transport so logged mail looks right.
        config([
            'mail.from.address' => $smtp['from_address'] ?? config('mail.from.address'),
            'mail.from.name'    => $smtp['from_name'] ?? config('mail.from.name'),
        ]);

        if ($host === '') {
            // No SMTP configured: keep whatever the .env default is (usually "log")
            // so signups never fail just because email isn't set up yet.
            return;
        }

        $encryption = $smtp['encryption'] ?? 'tls';

        config([
            'mail.default'              => 'smtp',
            'mail.mailers.smtp.host'    => $host,
            'mail.mailers.smtp.port'    => (int) ($smtp['port'] ?? 587),
            'mail.mailers.smtp.username' => $smtp['username'] ?? null,
            'mail.mailers.smtp.password' => $smtp['password'] ?? null,
            // Laravel 11+ uses the "scheme" key; smtps => implicit TLS (port 465).
            'mail.mailers.smtp.scheme'  => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.encryption' => $encryption ?: null,
        ]);
    }

    /** Feed Google OAuth keys to Socialite via the standard services.google slot. */
    private function bridgeGoogle(array $google): void
    {
        $redirect = $google['redirect'] ?? '/auth/google/callback';

        // Allow either a full URL or a path (we prefix with APP_URL for paths).
        if ($redirect !== '' && ! preg_match('#^https?://#i', $redirect)) {
            $redirect = rtrim((string) config('app.url'), '/') . '/' . ltrim($redirect, '/');
        }

        config([
            'services.google' => [
                'client_id'     => $google['client_id'] ?? '',
                'client_secret' => $google['client_secret'] ?? '',
                'redirect'      => $redirect,
            ],
        ]);
    }
}
