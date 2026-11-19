<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

/**
 * Small WebP copies of large photos for cards and grids, e.g. /thumbs/480/media/photos/law.jpg.webp.
 * The first request writes public/thumbs/...; after that the web server serves the file
 * directly and Laravel is never hit again. Only the widths in WIDTHS are allowed.
 * Use App\Support\Thumb::url() in views.
 */
class ThumbnailController extends Controller
{
    public const WIDTHS = [480, 960];

    public function __invoke(int $width, string $source, string $path)
    {
        $base = realpath($source === 'media' ? public_path('media') : storage_path('app/public'));
        $webp = str_ends_with($path, '.webp');
        $requested = $webp ? substr($path, 0, -5) : $path;
        $original = realpath($base . DIRECTORY_SEPARATOR . $requested);

        // Only real images inside the allowed folder, asked for by their plain path: "../", "./" or "//"
        // would make the copy's path below (built from the URL) differ from the original's and land
        // anywhere, even on top of the original.
        abort_unless(
            in_array($width, self::WIDTHS, true)
                && $base && $original
                && str_starts_with($original, $base . DIRECTORY_SEPARATOR)
                && substr($original, strlen($base) + 1) === $requested
                && preg_match('/\.(jpe?g|png)$/i', $original),
            404
        );

        $target = public_path("thumbs/$width/$source/$path");
        File::ensureDirectoryExists(dirname($target));

        $this->resize($original, $target, $width, $webp);

        return response()->file($target, ['Cache-Control' => 'public, max-age=31536000, immutable', 'Content-Type' => $webp ? 'image/webp' : mime_content_type($target)]);
    }

    private function resize(string $from, string $to, int $width, bool $webp = false): void
    {
        [$w, $h, $type] = getimagesize($from);

        if ($w <= $width && ! $webp) {
            File::copy($from, $to);

            return;
        }

        $src = $type === IMAGETYPE_PNG ? imagecreatefrompng($from) : imagecreatefromjpeg($from);
        $dst = $w > $width ? imagescale($src, $width, (int) round($h * $width / $w), IMG_BICUBIC) : $src;

        if ($webp) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagewebp($dst, $to, 78);
        } elseif ($type === IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagepng($dst, $to, 8);
        } else {
            imageinterlace($dst, true);  // progressive
            imagejpeg($dst, $to, 78);
        }
    }
}
