<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Shared definition of the vendor profile fields so sign-up, Google profile
 * completion and the profile editor all stay in sync.
 */
class VendorProfile
{
    /** Social platforms we collect, in display order, with their icon + label. */
    public const SOCIALS = [
        'facebook'  => 'Facebook',
        'instagram' => 'Instagram',
        'x'         => 'X / Twitter',
        'tiktok'    => 'TikTok',
        'youtube'   => 'YouTube',
    ];

    /** Validation rules for the profile portion of a form. */
    public static function rules(): array
    {
        $rules = [
            'business_name' => 'required|string|max:255',
            'contact_name'  => 'required|string|max:255',
            'phone'         => 'nullable|string|max:50',
            'address'       => 'nullable|string|max:500',
            'website'       => 'nullable|url|max:255',
            'categories'    => 'nullable|array',
            'categories.*'  => 'string|max:100',
        ];

        foreach (array_keys(self::SOCIALS) as $key) {
            $rules["socials.$key"] = 'nullable|string|max:255';
        }

        return $rules;
    }

    /** Pull the validated profile fields out of a request into model attributes. */
    public static function attributes(Request $request): array
    {
        $socials = [];
        foreach (array_keys(self::SOCIALS) as $key) {
            $val = trim((string) $request->input("socials.$key", ''));
            if ($val !== '') {
                $socials[$key] = $val;
            }
        }

        // De-duplicate and tidy the chosen category names.
        $categories = [];
        foreach ((array) $request->input('categories', []) as $name) {
            $name = trim((string) $name);
            if ($name !== '' && ! in_array($name, $categories, true)) {
                $categories[] = $name;
            }
        }

        return [
            'business_name' => $request->input('business_name'),
            'contact_name'  => $request->input('contact_name'),
            'phone'         => $request->input('phone'),
            'address'       => $request->input('address'),
            'website'       => $request->input('website'),
            'socials'       => $socials ?: null,
            'categories'    => $categories ?: null,
        ];
    }
}
