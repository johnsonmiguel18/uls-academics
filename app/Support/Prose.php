<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Long content typed in the admin, as safe HTML. New content comes from the rich text
 * editor (HTML); content saved before it, and the seeded ULS pages, is Markdown.
 */
class Prose
{
    public static function html(?string $content): string
    {
        $html = self::isMarkdown($content) ? Str::markdown((string) $content) : (string) $content;

        return Str::sanitizeHtml($html);
    }

    /** Rich editor output always starts with a tag; Markdown never does. */
    public static function isMarkdown(?string $content): bool
    {
        return filled($content) && ! str_starts_with(ltrim($content), '<');
    }
}
