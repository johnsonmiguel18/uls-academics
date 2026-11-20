<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers on every response Laravel sends. Files Apache serves directly (CSS, JS, images)
 * get the basic ones from public/.htaccess.
 *
 * Content-Security-Policy, in two versions:
 * - public pages: scripts only from this site, and inline scripts only with this request's nonce
 *   (Vite::cspNonce(); @vite and Livewire add it themselves, Blade scripts carry nonce="...");
 * - admin (Filament, Livewire, Alpine): Filament has inline scripts without a nonce and Alpine
 *   evaluates expressions, so it needs 'unsafe-inline' and 'unsafe-eval' (with a nonce in the
 *   list, browsers would ignore 'unsafe-inline').
 * Styles allow 'unsafe-inline' on both, for style="..." attributes (SDG colors, Filament).
 * config('security.csp_report_only'): only report violations, or enforce the policy.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        // PHP adds X-Powered-By itself while expose_php is on (set expose_php = Off in php.ini too).
        header_remove('X-Powered-By');

        $headers = $response->headers;
        $headers->remove('X-Powered-By');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set(
            config('security.csp_report_only') ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy',
            $this->policy($request),
        );

        // Only over HTTPS (behind a proxy this needs TRUSTED_PROXIES), and only in production,
        // so a local or staging copy never pins a browser to HTTPS.
        if ($request->isSecure() && app()->isProduction()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function policy(Request $request): string
    {
        // The Filament panel and Livewire's own endpoints (updates, uploads).
        $admin = $request->is('admin', 'admin/*') || str_starts_with($request->path(), 'livewire');

        // A CDN in ASSET_URL serves CSS, JS, images and fonts.
        $cdn = array_filter([self::origin(config('app.asset_url'))]);

        // The Vite dev server, only on a local copy while "npm run dev" runs.
        $dev = [];
        if (app()->isLocal() && Vite::isRunningHot() && ($server = self::origin(trim((string) @file_get_contents(Vite::hotFile()))))) {
            $dev = [$server, preg_replace('/^http/', 'ws', $server)];
        }

        // Forms may post to this site's own host names (the Global subdomain searches the Academics host).
        $hosts = array_filter([
            self::origin(config('app.url')),
            config('app.global_domain') ? (parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https') . '://' . config('app.global_domain') : null,
        ]);

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => [
                "'self'",
                ...($admin ? ["'unsafe-inline'", "'unsafe-eval'"] : ["'nonce-" . Vite::cspNonce() . "'"]),
                ...$cdn, ...$dev,
            ],
            'style-src' => ["'self'", "'unsafe-inline'", ...$cdn, ...$dev],
            'img-src' => [
                "'self'", 'data:',
                // Admin: upload previews and the default avatar service; public: YouTube video thumbnails.
                ...($admin ? ['blob:', 'https://ui-avatars.com'] : ['https://i.ytimg.com']),
                ...$cdn,
            ],
            'font-src' => ["'self'", 'data:', ...$cdn],
            'connect-src' => ["'self'", ...$dev],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'", ...$hosts],
            'frame-ancestors' => ["'self'"],
        ];

        return collect($directives)
            ->map(fn (array $sources, string $name) => $name . ' ' . implode(' ', array_unique($sources)))
            ->implode('; ');
    }

    /** "https://cdn.example.com/path" → "https://cdn.example.com"; null when not a URL. */
    private static function origin(?string $url): ?string
    {
        $parts = parse_url((string) $url);

        if (empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }
}
