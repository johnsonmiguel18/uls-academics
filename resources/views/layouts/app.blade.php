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
    @stack('head')
</head>
<body class="ac-body">
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
                    <form class="ac-nav-search" action="{{ route('academics.programs') }}" method="get" role="search">
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

    <div id="content">
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

    <script>
        // Back to top: shows after 600 px, smooth scroll.
        const backToTop = document.getElementById('backToTop');
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        backToTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' }));
        const toggleBackToTop = () => backToTop.classList.toggle('is-visible', window.scrollY > 600);
        window.addEventListener('scroll', toggleBackToTop, { passive: true });
        toggleBackToTop();

        // Long lists: preview with a "Show all" button; button texts come from Site settings (data-more / data-less).
        // Without JavaScript everything stays visible.
        document.querySelectorAll('[data-clamp]').forEach((box) => {
            box.classList.add('is-clamped');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'ac-button ac-button-ghost ac-clamp-toggle';
            button.setAttribute('aria-controls', box.id);
            button.setAttribute('aria-expanded', 'false');
            button.textContent = box.dataset.more;
            button.addEventListener('click', () => {
                const open = box.classList.toggle('is-clamped') === false;
                button.setAttribute('aria-expanded', open);
                button.textContent = open ? box.dataset.less : box.dataset.more;
                if (! open) box.parentElement.scrollIntoView({ block: 'start', behavior: reduceMotion ? 'auto' : 'smooth' });
            });
            box.after(button);
        });

        // Mobile menu; the Colleges list is a native <details>, so it works without JavaScript.
        document.querySelector('.ac-menu-toggle')?.addEventListener('click', (event) => {
            const open = document.body.classList.toggle('ac-nav-open');
            event.currentTarget.setAttribute('aria-expanded', open);
        });
        document.addEventListener('click', (event) => {
            const menu = document.querySelector('.ac-nav-colleges[open]');
            if (menu && ! menu.contains(event.target)) menu.removeAttribute('open');
        });
    </script>
    @stack('scripts')

    @if ((int) Site::get('performance.vitals_sample_rate', 0) > 0)
        {{-- Core Web Vitals from real visits (a share set in Site settings → Admin and performance).
             Sends one small beacon when the visitor leaves the page; no cookies, no personal data. --}}
        <script>
            (() => {
                if (Math.random() * 100 >= {{ (int) Site::get('performance.vitals_sample_rate') }} || ! ('PerformanceObserver' in window)) return;
                const m = {}, observe = (type, cb) => { try { new PerformanceObserver((l) => l.getEntries().forEach(cb)).observe({ type, buffered: true, durationThreshold: 16 }); } catch (e) {} };
                let cls = 0, win = 0, last = 0, first = 0, inp = 0;
                observe('largest-contentful-paint', (e) => { m.LCP = e.startTime; });
                observe('paint', (e) => { if (e.name === 'first-contentful-paint') m.FCP = e.startTime; });
                observe('layout-shift', (e) => {   // CLS: largest burst of unexpected shifts
                    if (e.hadRecentInput) return;
                    if (win && e.startTime - last < 1000 && e.startTime - first < 5000) { win += e.value; } else { win = e.value; first = e.startTime; }
                    last = e.startTime; cls = Math.max(cls, win); m.CLS = cls;
                });
                observe('event', (e) => { if (e.interactionId) { inp = Math.max(inp, e.duration); m.INP = inp; } });  // slowest interaction
                const nav = performance.getEntriesByType('navigation')[0];
                if (nav) m.TTFB = nav.responseStart;
                let sent = false;
                addEventListener('visibilitychange', () => {
                    if (sent || document.visibilityState !== 'hidden') return;
                    sent = true;
                    navigator.sendBeacon('{{ route('vitals') }}', JSON.stringify({ path: location.pathname, device: matchMedia('(max-width: 760px)').matches ? 'mobile' : 'desktop', metrics: m }));
                });
            })();
        </script>
    @endif
</body>
</html>
