<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;

/**
 * Laravel's CSRF check, without the readable XSRF-TOKEN cookie it adds to every response for JavaScript
 * clients such as axios. This app does not use one: forms send the hidden _token field and Livewire (the admin)
 * sends the token from the page, so the cookie only exposed the token to scripts and showed up in reviews.
 * The token itself stays in the page (forms, admin): the browser must send it back, so it cannot be hidden.
 */
class ValidateCsrfToken extends Middleware
{
    protected $addHttpCookie = false;
}
