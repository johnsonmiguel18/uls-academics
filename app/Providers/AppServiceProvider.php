<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\News;
use App\Observers\ContentActivityObserver;
use App\Observers\UserActivityObserver;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Event;
use App\Services\ActivityLogger;
use App\Observers\RolePermissionActivityObserver;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Proxies in front of the site (TRUSTED_PROXIES), so HTTPS and the visitor's IP are seen correctly.
        if (filled(config('security.trusted_proxies'))) {
            \Illuminate\Http\Middleware\TrustProxies::at(config('security.trusted_proxies') === '*'
                ? '*'
                : array_map('trim', explode(',', config('security.trusted_proxies'))));
        }

        News::observe(ContentActivityObserver::class);
        // Academics content is edited by many college admins: keep an audit trail.
        foreach ([\App\Models\College::class, \App\Models\Department::class, \App\Models\Program::class, \App\Models\FacultyMember::class, \App\Models\Document::class] as $model) {
            $model::observe(ContentActivityObserver::class);
        }
        User::observe(UserActivityObserver::class);

        // Every change to public content refreshes cached pages, menus, lists and settings.
        foreach ([\App\Models\College::class, \App\Models\Department::class, \App\Models\Program::class, \App\Models\Accreditation::class,
            \App\Models\FacultyMember::class, \App\Models\AcademicEvent::class, \App\Models\Document::class, News::class,
            \App\Models\SiteSetting::class, \App\Models\Term::class] as $model) {
            $model::observe(\App\Observers\ContentChangedObserver::class);
        }

        // Catch N+1 queries during development and tests (a missing ->with() fails loudly).
        \Illuminate\Database\Eloquent\Model::preventLazyLoading(! $this->app->isProduction());

        // Header and footer both list the published colleges (one small query per page).
        View::composer('layouts.app', fn ($view) => $view->with(
            'navColleges',
            \Illuminate\Support\Facades\Cache::rememberForever(
                'nav-colleges:' . \App\Support\ContentVersion::current(),
                fn () => \App\Models\College::published()->get(['name', 'slug'])->map->only('name', 'slug')->all(),
            ),
        ));
        Role::observe(RolePermissionActivityObserver::class);
        Permission::observe(RolePermissionActivityObserver::class);

            Event::listen(Login::class, function (Login $event): void {
    app(ActivityLogger::class)->record(
        'auth.login',
        'User successfully logged in.',
        $event->user instanceof \Illuminate\Database\Eloquent\Model
            ? $event->user
            : null,
        ['guard' => $event->guard]
    );
});

Event::listen(Logout::class, function (Logout $event): void {
    app(ActivityLogger::class)->record(
        'auth.logout',
        'User logged out.',
        $event->user instanceof \Illuminate\Database\Eloquent\Model
            ? $event->user
            : null,
        ['guard' => $event->guard]
    );
});

Event::listen(Failed::class, function (Failed $event): void {
    app(ActivityLogger::class)->record(
        'auth.failed',
        'A login attempt failed.',
        $event->user instanceof \Illuminate\Database\Eloquent\Model
            ? $event->user
            : null,
        ['guard' => $event->guard]
    );
});
    }
}
