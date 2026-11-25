<?php

namespace App\Support;

use App\Models\College;

/**
 * Colors chosen in the admin (college colors, SDG colors), as CSS classes in one <style> block in the page
 * head that carries the CSP nonce: .college-{id} and .sdg-{n} (.sdg-{n}.is-tint for goals without stories).
 * Pages use the classes instead of style="..." attributes, so the Content-Security-Policy needs no
 * 'unsafe-inline' for styles. Values that are not #RRGGBB colors are left out (they come from admin input).
 */
class Theme
{
    public static function css(): string
    {
        return once(function () {
            $rules = [];

            foreach (Sdg::goals() as $n => [, $color, $ink, $tint, $tintInk]) {
                if (self::hex($color, $ink, $tint, $tintInk)) {
                    $rules[] = ".sdg-$n{--sdg:$color;--sdg-ink:$ink;--sdg-tint:$tint;--sdg-tint-ink:$tintInk}";
                    $rules[] = ".sdg-$n.is-tint{--sdg:$tint;--sdg-ink:$tintInk}";
                }
            }

            foreach (College::query()->get(['id', 'color']) as $college) {
                [$accent, $ink, $on] = [$college->accent(), $college->accentInk(), $college->accentOn()];
                if (self::hex($accent, $ink, $on)) {
                    $rules[] = ".college-{$college->id}{--college:$accent;--college-ink:$ink;--college-on:$on}";
                }
            }

            return implode('', $rules);
        });
    }

    /**
     * A rule for one page, e.g. its header photo: Theme::url('.ac-hero-split', '--ac-hero-image', $src).
     * The URL is quoted and escaped so it cannot end the rule or the <style> block.
     */
    public static function url(string $selector, string $property, string $url): string
    {
        $safe = str_replace(['\\', '"', '<', '>', "\n", "\r"], ['\\\\', '\\"', '%3C', '%3E', '', ''], $url);

        return "$selector{{$property}:url(\"$safe\")}";
    }

    private static function hex(string ...$colors): bool
    {
        foreach ($colors as $color) {
            if (! preg_match('/^#[0-9a-f]{6}$/i', $color)) {
                return false;
            }
        }

        return true;
    }
}
