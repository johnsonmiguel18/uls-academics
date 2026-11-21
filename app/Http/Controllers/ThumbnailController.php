<?php

namespace App\Http\Controllers;

use App\Support\Thumb;

/**
 * Makes a missing thumbnail on its first request, e.g. /thumbs/480/media/photos/law.jpg.webp (most are made
 * ahead of time: see App\Support\Thumb). After that the web server serves the file directly and Laravel is
 * never hit again. Only the widths in Thumb::WIDTHS are allowed. Use Thumb::url() in views.
 */
class ThumbnailController extends Controller
{
    public const WIDTHS = Thumb::WIDTHS;

    public function __invoke(int $width, string $source, string $path)
    {
        $target = Thumb::make($width, $source, $path);
        abort_unless($target, 404);

        return response()->file($target, ['Cache-Control' => 'public, max-age=31536000, immutable', 'Content-Type' => str_ends_with($path, '.webp') ? 'image/webp' : mime_content_type($target)]);
    }
}
