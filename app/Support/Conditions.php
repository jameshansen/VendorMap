<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * The global vendor conditions / liability / rules document. Stored as a single
 * markdown file in the project root so the site owner can keep it in version
 * control, and edited via the admin panel. Rendered to HTML at booking time.
 */
class Conditions
{
    public static function path(): string
    {
        return base_path('vendor-conditions.md');
    }

    /** Raw markdown source (empty string if the file is missing). */
    public static function text(): string
    {
        $path = self::path();

        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    /** Rendered HTML for display. */
    public static function html(): string
    {
        $md = trim(self::text());

        return $md === '' ? '' : Str::markdown($md);
    }

    /** Persist new markdown content. */
    public static function save(string $markdown): void
    {
        file_put_contents(self::path(), str_replace("\r\n", "\n", $markdown));
    }
}
