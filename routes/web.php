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

// Global Engagement & Linkages: a page of this site at /global. If the old Global subdomain is still pointed here
// (GLOBAL_DOMAIN, e.g. global.uls.edu.ph), every address on it goes to the Academics home (deans' decision,
// November 2026); the Global page is reached from the Academics menu.
$global = function () {
    Route::get('/', [GlobalController::class, 'index'])->middleware(\App\Http\Middleware\CachePublicPages::class)->name('global.home');
    // No CSRF token: the page is cached for everyone. Rate limit and a hidden trap field keep spam out.
    Route::post('/inquiries', [GlobalController::class, 'inquire'])
        ->middleware('throttle:5,10')
        ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
        ->name('global.inquire');
};
if (config('app.global_domain')) {
    Route::domain(config('app.global_domain'))->group(function () {
        Route::get('/{any?}', fn () => redirect()->away(rtrim((string) config('app.url'), '/') . '/', 301))->where('any', '.*');
    });
}
Route::prefix('global')->domain(config('app.global_domain') ? parse_url(config('app.url'), PHP_URL_HOST) : null)->group($global);

// ULS Academics: a standalone site. The main university website links here.
// With GLOBAL_DOMAIN set, these pages stay on APP_URL's host (the subdomain only redirects here).
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
// News editor preview: the story as visitors will see it, in any status (drafts too). Shown in an
// iframe on the edit page; only for people who may edit that story (college admins: their own stories).
Route::get('/admin/news/{id}/preview', function (int $id) {
    abort_unless(\App\Filament\Resources\News\NewsResource::canViewAny() && (auth()->user()?->can('edit_news') ?? false), 403);
    $article = \App\Filament\Resources\News\NewsResource::getEloquentQuery()->whereKey($id)->firstOrFail();

    return response()
        ->view('news.show', ['article' => $article, 'relatedNews' => collect(), 'previousNews' => null, 'nextNews' => null])
        ->header('X-Robots-Tag', 'noindex, nofollow')
        ->header('Cache-Control', 'no-store, private');
})->whereNumber('id')->name('admin.news.preview');

Route::get('/thumbs/{width}/{source}/{path}', ThumbnailController::class)
    ->where(['width' => '480|960', 'source' => 'media|storage', 'path' => '.+'])
    ->name('thumbs');
