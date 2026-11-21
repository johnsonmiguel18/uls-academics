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

    /**
     * Whether the built site scripts can be loaded: "npm run build" has written public/build, or
     * "npm run dev" is running. If not, pages still work without their scripts (menus fall back to
     * plain links) instead of failing, and a warning is logged at most once an hour.
     */
    public static function viteReady(): bool
    {
        if (\Illuminate\Support\Facades\Vite::isRunningHot() || is_file(public_path('build/manifest.json'))) {
            return true;
        }

        if (! app()->runningUnitTests() && \Illuminate\Support\Facades\Cache::add('vite-build-missing-warned', true, 3600)) {
            logger()->warning('Site scripts are not built: run "npm ci && npm run build". Pages are served without them.');
        }

        return false;
    }
}
