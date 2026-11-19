<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogDeniedAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 403) {
            try {
                app(ActivityLogger::class)->record(
                    'auth.denied',
                    'A request was denied with HTTP 403.',
                    $request->user(),
                    [
                        'method' => $request->method(),
                        'path' => $request->path(),
                        'status_code' => 403,
                    ]
                );
            } catch (Throwable $exception) {
                // Audit logging must not turn an existing 403 into a 500.
                report($exception);
            }
        }

        return $response;
    }
}
