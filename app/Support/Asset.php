<?php

namespace App\Support;

/**
 * asset() plus "?v=<last modified time>", so browsers can cache CSS/JS for a year
 * (see public/.htaccess) and still get edits immediately.
 */
class Asset
{
    public static function url(string $path): string
    {
        $file = public_path($path);

        return asset($path) . (is_file($file) ? '?v=' . filemtime($file) : '');
    }
}
