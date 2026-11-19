<?php

namespace App\Http\Middleware;

use App\Models\AcademicEvent;
use App\Models\News;
use App\Support\ContentVersion;
use App\Support\Site;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps finished public pages ready, so most visits skip the database and template work.
 *
 * Always fresh:
 * - the cache key holds ContentVersion, which changes on every admin save or delete;
 * - a page is kept no longer than until the next scheduled change (a story going live or
 *   being taken down, an event ending), and never longer than "Keep public pages ready for"
 *   in Site settings (0 switches this off).
 * Browsers revalidate with an ETag and get "304 Not Modified" when nothing changed.
 */
class CachePublicPages
{
    public function handle(Request $request, Closure $next): Response
    {
        $minutes = (int) Site::get('performance.page_cache_minutes', 0);

        // Pages showing a one-time message (form sent, validation errors) are never cached or served from cache.
        if (! $request->isMethodCacheable() || $minutes <= 0 || $request->user() || ($request->hasSession() && $request->session()->get('_flash.old'))) {
            return $next($request);
        }

        $key = 'page:' . ContentVersion::current() . ':' . sha1($request->fullUrl());
        $cached = Cache::get($key);

        if ($cached) {
            return $this->respond($request, $cached['html'], $cached['type'], 'HIT');
        }

        $response = $next($request);

        if ($response->getStatusCode() === 200 && str_starts_with((string) $response->headers->get('Content-Type'), 'text/html')) {
            $ttl = min($minutes * 60, self::secondsUntilNextScheduledChange());
            if ($ttl > 0) {
                Cache::put($key, ['html' => $response->getContent(), 'type' => $response->headers->get('Content-Type')], $ttl);
            }

            return $this->respond($request, $response->getContent(), $response->headers->get('Content-Type'), 'MISS', $response);
        }

        return $response;
    }

    private function respond(Request $request, string $html, string $type, string $state, ?Response $response = null): Response
    {
        $etag = '"' . md5($html) . '"';

        if ($request->headers->get('If-None-Match') === $etag) {
            return response('', 304, ['ETag' => $etag, 'X-Page-Cache' => $state]);
        }

        $response ??= response($html, 200, ['Content-Type' => $type]);
        $response->headers->set('ETag', $etag);
        $response->headers->set('X-Page-Cache', $state);
        // Browsers must check back each time (cheap thanks to the ETag), so admin changes show at once.
        $response->headers->set('Cache-Control', 'no-cache, private');

        return $response;
    }

    /** Seconds until something time-based changes on the public site; at most a day. */
    public static function secondsUntilNextScheduledChange(): int
    {
        $now = now();
        $next = collect([
            News::whereIn('status', ['published', 'scheduled'])->where('published_at', '>', $now)->min('published_at'),
            News::whereIn('status', ['published', 'scheduled'])->where('unpublish_at', '>', $now)->min('unpublish_at'),
            AcademicEvent::where('is_published', true)->where('ends_at', '>', $now)->min('ends_at'),
            AcademicEvent::where('is_published', true)->whereNull('ends_at')->where('starts_at', '>=', $now->copy()->startOfDay())->min('starts_at'),
        ])->filter()->map(fn ($date) => \Illuminate\Support\Carbon::parse($date))->min();

        // Events without an end date drop off the "upcoming" list at the end of their day.
        $nextMidnight = $now->copy()->addDay()->startOfDay();

        return (int) max(1, min(86400, $now->diffInSeconds($next ? min($next, $nextMidnight) : $nextMidnight)));
    }
}
