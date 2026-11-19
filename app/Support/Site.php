<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Read admin-managed site settings (Admin → Site settings). Cached until the next admin change.
 *
 *   Site::get('contact.phone')
 *   Site::text('college.sections.programs.heading', ['short' => 'CIT'])   // ":short" placeholders
 *   Site::sections('home')                                               // enabled sections, in order
 */
class Site
{
    public static function all(): array
    {
        return once(fn () => Cache::rememberForever(
            'site-settings:' . ContentVersion::current(),
            fn () => SiteSetting::query()->first()?->data ?? [],
        ));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return data_get(self::all(), $key, $default);
    }

    /** URL of the uploaded seal/logo, or the seal bundled with the site. */
    public static function logo(): string
    {
        $logo = self::get('contact.logo');

        return $logo ? asset('storage/' . $logo) : asset('media/uls-seal.png');
    }

    /** A text setting with :placeholders replaced; empty string when not set. */
    public static function text(string $key, array $replace = []): string
    {
        return self::fill((string) self::get($key, ''), $replace);
    }

    /** Replace :name placeholders, longest names first (so :college_name is not hit by :college). */
    public static function fill(?string $text, array $replace = []): string
    {
        uksort($replace, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($replace as $name => $value) {
            $text = str_replace(':' . $name, (string) $value, (string) $text);
        }

        return (string) $text;
    }

    /**
     * The sections of a page that are switched on, in the order set in the admin,
     * keyed by section type: ['about' => ['type' => 'about', 'heading' => ..., ...], ...].
     */
    public static function sections(string $page): array
    {
        return collect(self::get("$page.sections", []))
            ->filter(fn ($section) => ($section['enabled'] ?? true) && ! empty($section['type']))
            ->keyBy('type')
            ->all();
    }
}
