<?php

namespace App\Support;

use App\Models\Vendor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI-written summary of the vendor category mix for admins: which categories
 * are plentiful, which are sparse, and how approving the pending vendors would
 * tilt the balance against the organiser's configured goal.
 *
 * Talks to an Ollama server (Ollama Cloud or self-hosted) configured under
 * config('vendormap.ai_guidance'). The summary is cached and only regenerated
 * when the vendor table (or the config) actually changes, so most page loads
 * and emails reuse the cached text. Admin-only: never render this to vendors
 * or customers.
 */
class VendorGuidance
{
    private const CACHE_KEY = 'vendor-guidance';

    public static function enabled(): bool
    {
        return (bool) config('vendormap.ai_guidance.enabled');
    }

    /**
     * The current summary as ['text' => ..., 'generated_at' => ISO-8601], or
     * null when disabled or when no generation has ever succeeded. On API
     * failure the previous (stale) summary is returned rather than nothing.
     */
    public static function summary(): ?array
    {
        if (! self::enabled()) {
            return null;
        }

        $snapshot = self::snapshot();
        $cfg = config('vendormap.ai_guidance');
        $signature = sha1(json_encode([$snapshot, $cfg['goal'], $cfg['model'], $cfg['url']]));

        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached) && ($cached['signature'] ?? null) === $signature) {
            return $cached;
        }

        $text = self::generate($snapshot, $cfg);
        if ($text === null) {
            return is_array($cached) ? $cached : null;
        }

        $fresh = [
            'signature' => $signature,
            'text' => $text,
            'generated_at' => now()->toIso8601String(),
        ];
        Cache::forever(self::CACHE_KEY, $fresh);

        return $fresh;
    }

    /** Everything the AI needs to reason about the mix — also the change detector. */
    private static function snapshot(): array
    {
        $counts = [];
        foreach (Vendor::where('status', 'approved')->orderBy('id')->pluck('categories') as $categories) {
            foreach ((array) $categories as $name) {
                $name = trim((string) $name);
                if ($name !== '') {
                    $counts[$name] = ($counts[$name] ?? 0) + 1;
                }
            }
        }
        arsort($counts);

        $pending = Vendor::where('status', 'pending')->orderBy('id')
            ->get(['business_name', 'categories'])
            ->map(fn ($v) => [
                'business' => $v->business_name,
                'categories' => array_values(array_filter(array_map('trim', (array) $v->categories))),
            ])
            ->all();

        return [
            'approved_total' => Vendor::where('status', 'approved')->count(),
            'approved_by_category' => $counts,
            'pending' => $pending,
        ];
    }

    private static function generate(array $snapshot, array $cfg): ?string
    {
        try {
            $request = Http::connectTimeout(5)->timeout(60);
            if (($cfg['api_key'] ?? '') !== '') {
                $request = $request->withToken($cfg['api_key']);
            }

            $response = $request->post($cfg['url'] . '/api/chat', [
                'model' => $cfg['model'],
                'messages' => [['role' => 'user', 'content' => self::prompt($snapshot, $cfg)]],
                'stream' => false,
                'think' => false,
            ]);

            $text = trim((string) $response->json('message.content'));

            if (! $response->successful() || $text === '') {
                Log::warning('VendorGuidance: generation failed', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);

                return null;
            }

            return $text;
        } catch (\Throwable $e) {
            Log::warning('VendorGuidance: generation failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private static function prompt(array $snapshot, array $cfg): string
    {
        $goal = trim((string) $cfg['goal']) ?:
            'A balanced, varied mix of vendor categories, with no single category dominating.';

        $approved = $snapshot['approved_by_category']
            ? collect($snapshot['approved_by_category'])->map(fn ($n, $cat) => "- {$cat}: {$n}")->implode("\n")
            : '(none yet)';

        $pending = $snapshot['pending']
            ? collect($snapshot['pending'])->map(fn ($v) => '- ' . $v['business'] . ' — ' .
                ($v['categories'] ? implode(', ', $v['categories']) : 'no categories given'))->implode("\n")
            : '(none)';

        return <<<PROMPT
You are helping the organiser of a small community market manage their vendor line-up.

The organiser's goal for the vendor mix:
{$goal}

Approved vendors: {$snapshot['approved_total']} in total. Number of vendors per category (a vendor can be in several):
{$approved}

Vendors awaiting approval, with their categories:
{$pending}

Write a short summary for the organiser (2-4 sentences, plain text only, no markdown, no headings, no preamble):
1. Describe the current spread in plain terms, e.g. "You have lots of X, a few Y and some Z."
2. If vendors are awaiting approval, say how approving them would tilt the mix, and name any category that would end up over-represented, e.g. "If you approve these vendors you will have too many X."
3. Call out any pending vendor whose categories do not fit the organiser's goal.
Use the actual category and business names. Do not invent data. If everything fits the goal, say so briefly.
PROMPT;
    }
}
