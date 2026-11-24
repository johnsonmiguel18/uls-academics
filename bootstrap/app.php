<?php

use App\Http\Middleware\LogDeniedAccess;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // CSRF check without the XSRF-TOKEN cookie (see the class).
        $middleware->web(replace: [
            Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class => ValidateCsrfToken::class,
        ]);
        $middleware->append(LogDeniedAccess::class);
        // Security headers and Content-Security-Policy on every response (public site, admin, JSON).
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
