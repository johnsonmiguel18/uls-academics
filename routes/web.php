<?php

use App\Http\Controllers\AcademicsController;
use App\Http\Controllers\GlobalController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\SdgController;
use App\Http\Controllers\ThumbnailController;
use App\Models\News;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Global Engagement & Linkages: on its own subdomain when GLOBAL_DOMAIN is set (e.g. global.uls.edu.ph),
// otherwise under /global. Registered first, so its "/" wins on that subdomain.
$global = function () {
    Route::get('/', [GlobalController::class, 'index'])->middleware(\App\Http\Middleware\CachePublicPages::class)->name('global.home');
    // No CSRF token: the page is cached for everyone. Rate limit and a hidden trap field keep spam out.
    Route::post('/inquiries', [GlobalController::class, 'inquire'])
        ->middleware('throttle:5,10')
        ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
        ->name('global.inquire');
};
config('app.global_domain')
    ? Route::domain(config('app.global_domain'))->group($global)
    : Route::prefix('global')->group($global);

// ULS Academics: a standalone site. The main university website links here.
// With GLOBAL_DOMAIN set, these pages stay on APP_URL's host, so links from Global point back there.
// Public pages are kept ready (App\Http\Middleware\CachePublicPages) and refreshed on every admin change.
Route::middleware(\App\Http\Middleware\CachePublicPages::class)
    ->domain(config('app.global_domain') ? parse_url(config('app.url'), PHP_URL_HOST) : null)
    ->group(function () {
    Route::controller(AcademicsController::class)->name('academics.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/programs', 'programs')->name('programs');
        Route::get('/programs/{program:slug}', 'program')->name('program');
        Route::get('/colleges/{college:slug}', 'college')->name('college');
        Route::get('/colleges/{college:slug}/{department:slug}', 'department')->scopeBindings()->name('department');
    });

    Route::get('/sdgs', [SdgController::class, 'index'])->name('sdgs.index');
    Route::get('/sdgs/{goal}', [SdgController::class, 'show'])->whereNumber('goal')->name('sdgs.show');

    Route::get('/news', [NewsController::class, 'index'])->name('news.index');
    Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');

    // With Global on its own subdomain, the old /global address (and menu links to it) still works.
    if (config('app.global_domain')) {
        Route::redirect('/global', (parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https') . '://' . config('app.global_domain'), 301);
    }
});

// Header search: programs first, then news.
Route::get('/search/suggestions', function (Request $request) {
    $term = is_string($request->query('q')) ? trim($request->query('q')) : '';

    if (mb_strlen($term) < 2) {
        return response()->json([]);
    }

    $programs = Program::published()
        ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"))
        ->orderBy('name')->limit(5)->get(['name', 'slug'])
        ->map(fn ($p) => ['title' => $p->name, 'type' => 'Program', 'url' => route('academics.program', $p->slug)]);

    $news = News::published()
        ->where(fn ($q) => $q->where('title', 'like', "%{$term}%")->orWhere('excerpt', 'like', "%{$term}%"))
        ->latest('published_at')->limit(3)->get(['title', 'slug'])
        ->map(fn ($n) => ['title' => $n->title, 'type' => 'News', 'url' => route('news.show', $n->slug)]);

    return response()->json($programs->concat($news)->values());
})->name('search.suggestions');

// Loading-speed measurements from real visits (Core Web Vitals); see the dashboard.
Route::post('/vitals', \App\Http\Controllers\VitalsController::class)
    ->middleware('throttle:30,1')
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
    ->name('vitals');

// Resized photos for cards; written to public/thumbs on first request, served as files after.
Route::get('/thumbs/{width}/{source}/{path}', ThumbnailController::class)
    ->where(['width' => '480|960', 'source' => 'media|storage', 'path' => '.+'])
    ->name('thumbs');
