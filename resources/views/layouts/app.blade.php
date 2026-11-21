@use('App\Support\Site')
@php
    // Global Engagement pages pass ['area' => 'global'] to get their own name, menu and button.
    $isGlobal = ($area ?? null) === 'global';
    $homeUrl = $isGlobal ? route('global.home') : route('academics.index');
    $brandLabel = Site::text($isGlobal ? 'global.brand_label' : 'header.brand_label');
    $menuLinks = Site::get($isGlobal ? 'global.menu' : 'header.menu', []);
    // Relative links ("/programs") belong to the Academics site, also when shown on the Global subdomain.
    $linkUrl = fn (?string $u) => match (true) {
        str_starts_with((string) $u, '#'), (bool) preg_match('#^[a-z]+:#i', (string) $u) => $u,
        (bool) config('app.global_domain') => rtrim(config('app.url'), '/') . '/' . ltrim((string) $u, '/'),
        default => url($u ?: '/'),
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', Site::text('contact.site_name'))</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @if (config('app.asset_url'))
        <link rel="preconnect" href="{{ config('app.asset_url') }}" crossorigin>
    @endif

    {{-- Core styles on every page. Pages add their own: 'base-styles' load before the core (news pages
         reuse the main website's styles), 'styles' after it. --}}
    @stack('base-styles')
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/academics.css') }}">
    @stack('styles')
    {{-- Site scripts (resources/js/site.js), built and obfuscated by "npm run build". --}}
    @if (\App\Support\Asset::viteReady())
        @vite('resources/js/site.js')
    @endif
    @stack('head')
</head>
<body class="ac-body" @if ((int) Site::get('performance.vitals_sample_rate', 0) > 0) data-vitals-rate="{{ (int) Site::get('performance.vitals_sample_rate') }}" data-vitals-url="{{ route('vitals') }}" @endif>
    <a class="ac-skip" href="#content">{{ Site::text('a11y.skip_link') }}</a>

    <header class="ac-site-header">
        @if (Site::text('header.topbar_link_label') || Site::get('header.show_contact'))
            <div class="ac-topbar">
                <div class="ac-container">
                    @if (Site::text('header.topbar_link_label') && Site::text('contact.main_site_url'))
                        <a href="{{ Site::text('contact.main_site_url') }}">{{ Site::text('header.topbar_link_label') }} <span aria-hidden="true">↗</span></a>
                    @endif
                    @if (Site::get('header.show_contact'))
                        <span>
                            {{ Site::text('contact.address') }}
                            @if (Site::text('contact.phone')) · <a href="tel:{{ preg_replace('/[^0-9+]/', '', Site::text('contact.phone')) }}">{{ Site::text('contact.phone') }}</a>@endif
                        </span>
                    @endif
                </div>
            </div>
        @endif
        <div class="ac-container ac-masthead">
            <a class="ac-brand" href="{{ $homeUrl }}" aria-label="{{ $brandLabel ?: Site::text('contact.site_name') }} home">
                <img src="{{ Site::logo() }}" alt="" width="48" height="48">
                @include('partials.wordmark', ['class' => 'ac-wordmark'])
                @if ($brandLabel)<span class="ac-brand-label">{{ $brandLabel }}</span>@endif
            </a>

            <button class="ac-menu-toggle" type="button" aria-expanded="false" aria-controls="acNav" aria-label="{{ Site::text('a11y.open_menu') }}">
                <span></span>
            </button>

            <nav class="ac-nav" id="acNav" aria-label="{{ Site::text('a11y.main_navigation') }}">
                @if (! $isGlobal && Site::text('header.colleges_menu_label') && $navColleges)
                    <details class="ac-nav-colleges">
                        <summary>{{ Site::text('header.colleges_menu_label') }}</summary>
                        <ul>
                            @foreach ($navColleges as $navCollege)
                                <li><a href="{{ route('academics.college', $navCollege['slug']) }}">{{ $navCollege['name'] }}</a></li>
                            @endforeach
                        </ul>
                    </details>
                @endif
                @foreach ($menuLinks as $link)
                    <a href="{{ $linkUrl($link['url'] ?? '/') }}" @if ($linkUrl($link['url'] ?? '/') === url()->current()) aria-current="page" @endif>{{ $link['label'] ?? '' }}</a>
                @endforeach
                @if ($isGlobal && Site::text('global.menu_button'))
                    <a class="ac-button ac-button-light ac-nav-enroll" href="#connect">{{ Site::text('global.menu_button') }}</a>
                @endif
                @if (! $isGlobal && Site::text('header.search_placeholder'))
                    <form class="ac-nav-search" action="{{ route('academics.programs') }}" method="get" role="search" aria-label="{{ Site::text('header.search_placeholder') }}">
                        <label class="sr-only" for="acNavSearch">{{ Site::text('header.search_placeholder') }}</label>
                        <input id="acNavSearch" type="search" name="q" placeholder="{{ Site::text('header.search_placeholder') }}">
                    </form>
                @endif
                @if (! $isGlobal && Site::text('header.enroll_label') && Site::text('contact.enroll_url'))
                    <a class="ac-button ac-button-light ac-nav-enroll" href="{{ Site::text('contact.enroll_url') }}" rel="noopener">{{ Site::text('header.enroll_label') }} <span aria-hidden="true">↗</span></a>
                @endif
            </nav>
        </div>
    </header>

    {{-- tabindex="-1": "Skip to content" moves keyboard focus here in every browser --}}
    <div id="content" tabindex="-1">
        @yield('content')
    </div>

    <footer class="ac-footer">
        <div class="ac-container ac-footer-grid">
            <div>
                <a class="ac-brand" href="{{ $homeUrl }}" aria-label="{{ $brandLabel ?: Site::text('contact.site_name') }} home">
                    <img src="{{ Site::logo() }}" alt="" width="48" height="48" loading="lazy">
                    @include('partials.wordmark', ['class' => 'ac-wordmark'])
                </a>
                @if (Site::text('footer.about'))<p>{{ Site::text('footer.about') }}</p>@endif
            </div>
            @if (Site::text('footer.colleges_heading') && $navColleges)
                <div>
                    <h2>{{ Site::text('footer.colleges_heading') }}</h2>
                    <ul>
                        @foreach ($navColleges as $navCollege)
                            <li><a href="{{ route('academics.college', $navCollege['slug']) }}">{{ $navCollege['name'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @foreach (Site::get('footer.columns', []) as $column)
                <div>
                    <h2>{{ $column['heading'] ?? '' }}</h2>
                    <ul>
                        @foreach ($column['links'] ?? [] as $link)
                            <li><a href="{{ $linkUrl($link['url'] ?? '/') }}">{{ $link['label'] ?? '' }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
        @if (Site::text('footer.copyright'))
            <div class="ac-container ac-footer-bottom">
                <span>{{ Site::text('footer.copyright', ['year' => date('Y')]) }}</span>
            </div>
        @endif
    </footer>

    <button type="button" class="back-to-top" id="backToTop" aria-label="{{ Site::text('header.back_to_top') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
    </button>

    @stack('scripts')

</body>
</html>
