<?php

namespace App\Support;

/**
 * URL of a resized WebP copy (see ThumbnailController). $path is relative to
 * public/media ('media') or storage/app/public ('storage'), as stored in the database.
 * WebP is about a third smaller than JPEG at the same quality.
 */
class Thumb
{
    public static function url(string $path, int $width = 480, string $source = 'storage'): string
    {
        $path = ltrim($path, '/');

        // Only JPEG/PNG get resized copies; anything else (SVG, GIF, WebP uploads) is served as is.
        if (! preg_match('/\.(jpe?g|png)$/i', $path)) {
            return asset(($source === 'media' ? 'media/' : 'storage/') . $path);
        }

        return asset("thumbs/$width/$source/$path.webp");
    }

    /** srcset with both thumbnail sizes, for images shown at different sizes. */
    public static function srcset(string $path, string $source = 'storage'): string
    {
        return self::url($path, 480, $source) . ' 480w, ' . self::url($path, 960, $source) . ' 960w';
    }
}
