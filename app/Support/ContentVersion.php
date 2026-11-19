<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Once;

/**
 * One number that changes whenever anything shown on the public site is saved or deleted
 * in the admin. Every cached piece of the site (settings, lists, menus, whole pages) has this
 * number in its cache key, so an admin change is visible on the next page view.
 */
class ContentVersion
{
    private const KEY = 'content-version';

    public static function current(): int
    {
        return (int) Cache::rememberForever(self::KEY, fn () => (int) (microtime(true) * 1000));
    }

    public static function bump(): void
    {
        Cache::forever(self::KEY, max(self::current() + 1, (int) (microtime(true) * 1000)));
        Once::flush(); // values memoized earlier in this same request
    }
}
