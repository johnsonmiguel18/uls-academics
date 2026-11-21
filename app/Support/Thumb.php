<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Resized WebP copies of large photos for cards and grids, e.g. /thumbs/480/media/photos/law.jpg.webp.
 * $path is relative to public/media ('media') or storage/app/public ('storage'), as stored in the database.
 * WebP is about a third smaller than JPEG at the same quality.
 *
 * Copies are written to public/thumbs/..., where the web server then serves them directly. They are made
 * ahead of time when an image is saved in the admin (AppServiceProvider) and by `php artisan thumbs:warm`;
 * ThumbnailController still makes any missing copy on its first request.
 */
class Thumb
{
    public const WIDTHS = [480, 960];

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

    /** Both WebP sizes of an image, made now if missing. Returns how many were written. */
    public static function warm(string $path, string $source = 'storage'): int
    {
        $made = 0;
        foreach (self::WIDTHS as $width) {
            $target = public_path("thumbs/$width/$source/" . ltrim($path, '/') . '.webp');
            if (! is_file($target) && self::make($width, $source, ltrim($path, '/') . '.webp')) {
                $made++;
            }
        }

        return $made;
    }

    /**
     * Writes one copy and returns its file path, or null when the request is not allowed or not an image.
     * $path is the requested name as in the URL (".webp" added for a WebP copy).
     */
    public static function make(int $width, string $source, string $path): ?string
    {
        $base = realpath($source === 'media' ? public_path('media') : storage_path('app/public'));
        $webp = str_ends_with($path, '.webp');
        $requested = $webp ? substr($path, 0, -5) : $path;
        $original = realpath($base . DIRECTORY_SEPARATOR . $requested);

        // Only real images inside the allowed folder, asked for by their plain path: "../", "./" or "//"
        // would make the copy's path below (built from the URL) differ from the original's and land
        // anywhere, even on top of the original.
        if (! (in_array($width, self::WIDTHS, true)
            && in_array($source, ['media', 'storage'], true)
            && $base && $original
            && str_starts_with($original, $base . DIRECTORY_SEPARATOR)
            // realpath() gives backslashes on Windows (XAMPP); URLs always use "/".
            && str_replace(DIRECTORY_SEPARATOR, '/', substr($original, strlen($base) + 1)) === $requested
            && preg_match('/\.(jpe?g|png)$/i', $original))) {
            return null;
        }

        $target = public_path("thumbs/$width/$source/$path");
        File::ensureDirectoryExists(dirname($target));

        // Written to a temporary name first, so a visitor asking at the same moment never gets half a file.
        $temp = $target . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (! self::resize($original, $temp, $width, $webp)) {
            @unlink($temp);

            return null; // not a readable image after all
        }
        rename($temp, $target);

        return $target;
    }

    /** Writes the resized copy; false when the file is not a readable JPEG or PNG. */
    private static function resize(string $from, string $to, int $width, bool $webp = false): bool
    {
        $info = @getimagesize($from);

        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            return false;
        }

        [$w, $h, $type] = $info;

        if ($w <= $width && ! $webp) {
            return File::copy($from, $to);
        }

        $src = $type === IMAGETYPE_PNG ? @imagecreatefrompng($from) : @imagecreatefromjpeg($from);

        if (! $src) {
            return false;
        }

        $dst = $w > $width ? imagescale($src, $width, (int) round($h * $width / $w), IMG_BICUBIC) : $src;

        if ($webp) {
            // Palette PNGs (e.g. small logos) cannot be written as WebP: convert to full colour first.
            if (! imageistruecolor($dst)) {
                imagepalettetotruecolor($dst);
            }
            imagealphablending($dst, false);
            imagesavealpha($dst, true);

            return imagewebp($dst, $to, 78);
        }

        if ($type === IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);

            return imagepng($dst, $to, 8);
        }

        imageinterlace($dst, true);  // progressive

        return imagejpeg($dst, $to, 78);
    }
}
