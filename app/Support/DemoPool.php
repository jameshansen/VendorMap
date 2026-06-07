<?php

namespace App\Support;

use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Manages a fixed pool of pre-created MySQL databases used for the public demo.
 *
 * Each visitor is mapped (via a signed cookie value "slot:token") to one slot.
 * A small JSON registry tracks which token currently owns each slot and when it
 * was last active. Allocation hands out free slots first, then recycles the
 * least-recently-used slot when the pool is full — resetting it to the demo
 * baseline so the new visitor starts clean. Databases are created once by
 * `php artisan demo:setup`; at runtime we only switch + reseed, never
 * create/drop.
 */
class DemoPool
{
    public function size(): int
    {
        return (int) config('vendormap.demo.pool_size', 25);
    }

    public function dbName(int $slot): string
    {
        return config('vendormap.demo.db_prefix', 'vendormap_demo_') . $slot;
    }

    private function registryPath(): string
    {
        return storage_path('app/demo/registry.json');
    }

    /**
     * Resolve the slot for a visitor.
     *
     * @return array{0:int,1:string,2:bool}  [slot, token, needsReseed]
     */
    public function allocate(?string $cookie): array
    {
        [$wantSlot, $wantToken] = $this->parseCookie($cookie);

        return $this->withRegistry(function (array &$reg) use ($wantSlot, $wantToken) {
            // 1. Returning visitor with a still-valid claim — keep their data.
            if ($wantSlot && isset($reg[$wantSlot])
                && $reg[$wantSlot]['token'] !== null
                && hash_equals((string) $reg[$wantSlot]['token'], (string) $wantToken)) {
                $reg[$wantSlot]['active'] = time();

                return [$wantSlot, $wantToken, false];
            }

            // 2. Prefer a never-used (free) slot — already clean from setup.
            foreach ($reg as $slot => $info) {
                if ($info['token'] === null) {
                    $token = $this->newToken();
                    $reg[$slot] = ['token' => $token, 'active' => time()];

                    return [$slot, $token, false];
                }
            }

            // 3. Pool full: recycle the least-recently-used slot (needs reseed).
            $slot = $this->leastRecentlyUsed($reg);
            $token = $this->newToken();
            $reg[$slot] = ['token' => $token, 'active' => time()];

            return [$slot, $token, true];
        });
    }

    /** Point the default mysql connection at a slot's database for this request. */
    public function switchTo(int $slot): void
    {
        config(['database.connections.mysql.database' => $this->dbName($slot)]);
        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    /** Reset a slot's database back to the demo baseline. */
    public function reseed(int $slot): void
    {
        $this->switchTo($slot);

        Artisan::call('migrate:fresh', [
            '--database' => 'mysql',
            '--seed' => true,
            '--seeder' => DemoSeeder::class,
            '--force' => true,
        ]);
    }

    /**
     * One-time provisioning: create each pool database (if missing), migrate and
     * seed it, and reset the registry. Safe to re-run.
     */
    public function setup(callable $progress = null): void
    {
        File::ensureDirectoryExists(dirname($this->registryPath()));
        $original = config('database.connections.mysql.database');

        // Create the databases up-front using the current (base) connection.
        for ($slot = 1; $slot <= $this->size(); $slot++) {
            $name = $this->dbName($slot);
            DB::connection('mysql')->statement(
                "CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );
        }

        // Migrate + seed each slot.
        for ($slot = 1; $slot <= $this->size(); $slot++) {
            $this->reseed($slot);
            if ($progress) {
                $progress($slot, $this->dbName($slot));
            }
        }

        // Restore the base connection and clear the registry (all slots free).
        config(['database.connections.mysql.database' => $original]);
        DB::purge('mysql');
        DB::reconnect('mysql');

        $fresh = [];
        for ($slot = 1; $slot <= $this->size(); $slot++) {
            $fresh[$slot] = ['token' => null, 'active' => 0];
        }
        File::put($this->registryPath(), json_encode($fresh, JSON_PRETTY_PRINT));
    }

    // -- internals ----------------------------------------------------------

    /** Open the registry under an exclusive lock, run $fn, persist changes. */
    private function withRegistry(callable $fn): array
    {
        File::ensureDirectoryExists(dirname($this->registryPath()));
        $handle = fopen($this->registryPath(), 'c+');
        if ($handle === false) {
            throw new \RuntimeException('Demo registry is not writable.');
        }

        try {
            flock($handle, LOCK_EX);
            $raw = stream_get_contents($handle);
            $reg = $this->normalize(json_decode($raw ?: '[]', true) ?: []);

            $result = $fn($reg);

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($reg, JSON_PRETTY_PRINT));
            fflush($handle);

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /** Ensure every slot 1..N exists in the registry. */
    private function normalize(array $reg): array
    {
        $out = [];
        for ($slot = 1; $slot <= $this->size(); $slot++) {
            $out[$slot] = [
                'token' => $reg[$slot]['token'] ?? null,
                'active' => (int) ($reg[$slot]['active'] ?? 0),
            ];
        }

        return $out;
    }

    private function leastRecentlyUsed(array $reg): int
    {
        $bestSlot = 1;
        $bestActive = PHP_INT_MAX;
        foreach ($reg as $slot => $info) {
            if ($info['active'] < $bestActive) {
                $bestActive = $info['active'];
                $bestSlot = $slot;
            }
        }

        return $bestSlot;
    }

    /** @return array{0:?int,1:?string} */
    private function parseCookie(?string $cookie): array
    {
        if (! $cookie || ! str_contains($cookie, ':')) {
            return [null, null];
        }
        [$slot, $token] = explode(':', $cookie, 2);

        return [ctype_digit($slot) ? (int) $slot : null, $token ?: null];
    }

    private function newToken(): string
    {
        return bin2hex(random_bytes(8));
    }
}
