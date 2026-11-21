<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Pages\BasePage;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Enums\ThemeMode;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Save / Cancel stay visible at the bottom of long forms.
        BasePage::stickyFormActions();

        
        return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->login()
        ->brandName(fn (): string => \App\Support\Site::text('admin.brand_name') ?: config('app.name'))
        // Same seal + wordmark as the website header (resources/views/filament/brand.blade.php).
        ->brandLogo(fn () => view('filament.brand'))
        ->brandLogoHeight('3rem')
        ->favicon(asset('favicon.ico'))
        ->colors([
        // Same royal blue as the public site.
        'primary' => Color::hex('#192a8a'),
    ])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->navigationGroups(['Academics', 'News & Events', 'Global engagement', 'Site', 'System Administration'])
            // Initials drawn locally for users without a profile picture (no outside avatar service).
            ->defaultAvatarProvider(\App\Filament\AvatarProviders\InitialsAvatar::class)
            // No ->spa(): with it, the browser's Back button could rebuild a page without Filament's
            // stylesheet (unstyled admin). Normal page loads are a little slower but always complete.
            // System/Light/Dark switcher in the user menu, starting on Light (the public site is light only).
            ->defaultThemeMode(ThemeMode::Light)
            ->sidebarCollapsibleOnDesktop()
            // ULS theme: plain CSS through render hooks, no Tailwind build needed.
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => '
                <link rel="stylesheet" href="' . \App\Support\Asset::url('css/admin.css') . '">')
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn (): string => '
                <a class="uls-sidebar-site" href="' . url('/') . '" target="_blank" rel="noopener" title="View public website">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/></svg>
                    <span class="uls-sidebar-site-label">View public website</span>
                </a>')
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, fn (): string => '
                <p class="uls-login-note">' . e(\App\Support\Site::text('admin.login_subheading')) . '</p>')
            ->renderHook(PanelsRenderHook::SIMPLE_LAYOUT_END, fn (): string => '
                <p class="uls-login-footer">&copy; ' . date('Y') . ' ' . e(\App\Support\Site::text('contact.university_name')) . ' · ' . e(\App\Support\Site::text('contact.address')) . '</p>')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
