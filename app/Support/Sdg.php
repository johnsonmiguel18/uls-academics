<?php

namespace App\Support;

use App\Models\Term;

/**
 * The Sustainable Development Goals, from the admin list "Sustainable Development Goals"
 * (key = goal number, label = name, color = official color). For each goal this adds a text
 * color that passes WCAG AA on it, and a pale low-saturation tint of the same hue with its
 * own AA text color, used for goals a college has no stories for yet.
 */
class Sdg
{
    /** [number => [name, color, ink, tint, tintInk]] */
    public static function goals(): array
    {
        return once(function () {
            $goals = [];
            foreach (Term::entries('sdg') as $number => $term) {
                $color = $term['color'] ?: '#6B7280';
                [$tint, $tintInk] = self::tint($color);
                $goals[(int) $number] = [$term['label'], $color, self::ink($color), $tint, $tintInk];
            }
            ksort($goals);

            return $goals;
        });
    }

    /** [number => "4 · Quality Education"] for admin pickers. */
    public static function options(): array
    {
        $options = [];
        foreach (self::goals() as $number => [$name]) {
            $options[$number] = "$number · $name";
        }

        return $options;
    }

    public static function name(int $number): ?string
    {
        return self::goals()[$number][0] ?? null;
    }

    /** White or dark text, whichever contrasts more with the color. */
    public static function ink(string $color): string
    {
        return self::contrast('#FFFFFF', $color) >= self::contrast('#1F2330', $color) ? '#FFFFFF' : '#1F2330';
    }

    /** Pale, desaturated tint of the hue, and the lightest text of that hue reaching 4.5:1 on it. */
    public static function tint(string $color): array
    {
        [$h, $s] = self::toHsl($color);
        $tint = self::fromHsl($h, $s * 0.40, 0.91);

        for ($l = 0.5; $l > 0; $l -= 0.005) {
            $ink = self::fromHsl($h, $s * 0.45, $l);
            if (self::contrast($ink, $tint) >= 4.55) {
                return [$tint, $ink];
            }
        }

        return [$tint, '#1F2330'];
    }

    public static function contrast(string $a, string $b): float
    {
        [$la, $lb] = [self::luminance($a), self::luminance($b)];

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    private static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return array_map(fn ($i) => hexdec(substr($hex, $i, 2)) / 255, [0, 2, 4]);
    }

    private static function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(fn ($v) => $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, self::rgb($hex));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private static function toHsl(string $hex): array
    {
        [$r, $g, $b] = self::rgb($hex);
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $d = $max - $min;

        if ($d == 0) {
            return [0, 0, $l];
        }

        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        $h = match ($max) {
            $r => fmod(($g - $b) / $d + 6, 6),
            $g => ($b - $r) / $d + 2,
            default => ($r - $g) / $d + 4,
        } / 6;

        return [$h, $s, $l];
    }

    private static function fromHsl(float $h, float $s, float $l): string
    {
        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;
        $channel = function (float $t) use ($p, $q): float {
            $t = fmod($t + 1, 1);

            return match (true) {
                $t < 1 / 6 => $p + ($q - $p) * 6 * $t,
                $t < 1 / 2 => $q,
                $t < 2 / 3 => $p + ($q - $p) * (2 / 3 - $t) * 6,
                default => $p,
            };
        };

        return sprintf('#%02X%02X%02X', ...array_map(fn ($t) => (int) round($channel($t) * 255), [$h + 1 / 3, $h, $h - 1 / 3]));
    }
}
