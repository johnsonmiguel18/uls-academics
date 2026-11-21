<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin avatar for users without a profile picture: their initials, drawn here as an SVG image,
 * so no name is sent to an outside service (Filament's default is ui-avatars.com).
 */
class InitialsAvatar implements AvatarProvider
{
    public function get(Model $record): string
    {
        // First letter of the first two words, skipping leading punctuation ("[SYSTEM] Admin" → "SA").
        $initials = collect(preg_split('/\s+/u', trim((string) Filament::getNameForDefaultAvatar($record))))
            ->map(fn (string $word) => mb_substr((string) preg_replace('/^[^\p{L}\p{N}]+/u', '', $word), 0, 1))
            ->filter()
            ->take(2)
            ->implode('');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">'
            . '<rect width="64" height="64" fill="#192a8a"/>'
            . '<text x="32" y="41" text-anchor="middle" font-family="Arial, sans-serif" font-size="26" font-weight="600" fill="#ffffff">'
            . htmlspecialchars(mb_strtoupper($initials ?: '?'), ENT_XML1) . '</text></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
