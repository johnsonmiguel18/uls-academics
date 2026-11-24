@extends('layouts.app')

@use('App\Support\Site')

@section('title', Site::text('home.meta_title') ?: Site::text('contact.site_name'))

@include('academics.partials.seo', [
    'title' => Site::text('home.meta_title') ?: Site::text('contact.site_name'),
    'description' => Site::text('home.meta_description') ?: Site::text('seo.default_description'),
    'image' => $colleges->first()?->banner_image,
    'schema' => [
        '@context' => 'https://schema.org',
        '@type' => 'CollegeOrUniversity',
        'name' => Site::text('contact.university_name'),
        'url' => Site::text('contact.main_site_url'),
        'address' => Site::text('seo.organization_address'),
        'department' => $colleges->map(fn ($c) => ['@type' => 'EducationalOrganization', 'name' => $c->name, 'url' => route('academics.college', $c)])->values(),
    ],
])

@if (isset($sections['news']))
    @push('styles')<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/news-card.css') }}">@endpush
@endif

@section('content')
<main class="ac-page">
    {{-- Hero: title, search and quick links (Site settings → Home page) --}}
    <section class="ac-hero ac-hero-home">
        <div class="ac-container ac-hero-home-grid">
          <div class="ac-hero-home-text">
            <nav class="ac-breadcrumb" aria-label="{{ \App\Support\Site::text('a11y.breadcrumb') }}">
                @if (Site::text('home.breadcrumb_parent') && Site::text('contact.main_site_url'))
                    <a href="{{ Site::text('contact.main_site_url') }}">{{ Site::text('home.breadcrumb_parent') }}</a>
                @endif
                <span aria-current="page">{{ Site::text('header.brand_label') ?: Site::text('contact.site_name') }}</span>
            </nav>
            @if (Site::text('home.kicker'))<p class="ac-kicker">{{ Site::text('home.kicker') }}</p>@endif
            <h1>{{ Site::text('home.headline') }}</h1>
            @if (Site::text('home.lead'))<p class="ac-lead">{{ Site::text('home.lead') }}</p>@endif

            <form class="ac-search" action="{{ route('academics.programs') }}" method="get" role="search" aria-label="{{ Site::text('home.search_label') }}">
                <label class="sr-only" for="ac-search-input">{{ Site::text('home.search_label') }}</label>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>
                <input id="ac-search-input" type="search" name="q" placeholder="{{ Site::text('home.search_placeholder') }}" autocomplete="off">
                <button type="submit">{{ Site::text('home.search_button') }}</button>
            </form>

            @if (Site::get('home.quick_links'))
                <ul class="ac-quick">
                    @foreach (Site::get('home.quick_links') as $link)
                        <li><a href="{{ url($link['url'] ?? '/') }}">{{ $link['label'] ?? '' }}</a></li>
                    @endforeach
                </ul>
            @endif
          </div>

            {{-- Hero photo (Site settings → Home page → Hero photo); a placeholder with the seal until one is set --}}
            @if (Site::get('home.hero_image'))
                <figure class="ac-hero-photo">
                    <img src="{{ \App\Support\Thumb::url(Site::get('home.hero_image'), 960) }}" width="960" height="720" alt="{{ Site::text('home.hero_image_alt') }}" fetchpriority="high" decoding="async">
                </figure>
            @else
                <div class="ac-hero-photo is-placeholder" aria-hidden="true">
                    <img src="{{ Site::logo() }}" alt="" width="140" height="140" decoding="async">
                </div>
            @endif
        </div>
    </section>

    {{-- The other sections, in the order and with the headings set in Site settings --}}
    @foreach ($sections as $type => $section)
        @switch($type)
            @case('stats')
                @if ($stats)
                    <section class="ac-stats" aria-label="{{ $section['heading'] ?? '' }}">
                        <div class="ac-container ac-stats-grid">
                            @foreach ($stats as $label => $value)
                                <div class="ac-stat"><strong>{{ $value }}</strong><span>{{ $label }}</span></div>
                            @endforeach
                        </div>
                    </section>
                @endif
                @break

            @case('colleges')
                @if ($colleges->isNotEmpty())
                    <section class="ac-section" id="colleges" aria-labelledby="colleges-heading">
                        <div class="ac-container">
                            @include('academics.partials.section-head', ['section' => $section, 'id' => 'colleges-heading', 'link' => route('academics.programs')])
                            <ul class="ac-college-grid">
                                @foreach ($colleges as $college)
                                    <li>
                                        <a class="ac-college-card" href="{{ route('academics.college', $college) }}" style="{{ $college->accentStyle() }}">
                                            <span @class(['ac-college-media', 'is-empty' => ! $college->banner_image])>
                                                @if ($college->banner_image)
                                                    <img src="{{ \App\Support\Thumb::url($college->banner_image) }}" srcset="{{ \App\Support\Thumb::srcset($college->banner_image) }}" sizes="(max-width: 600px) 92vw, 380px" width="480" height="270" alt="" loading="lazy" decoding="async">
                                                @endif
                                                @include('academics.partials.college-mark')
                                            </span>
                                            <span class="ac-college-body">
                                                <span class="ac-college-level">{{ \App\Models\Term::label('level', $college->level) }}</span>
                                                <strong>{{ $college->name }}</strong>
                                                <span class="ac-muted">{{ trans_choice(':count program|:count programs', $college->programs_count) }}@if ($college->dean_name) · {{ \Illuminate\Support\Str::before($college->dean_title ?? '', ',') ?: '' }}{{ $college->dean_title ? ': ' : '' }}{{ $college->dean_name }}@endif</span>
                                                @if (Site::text('home.college_card_link'))<span class="ac-link">{{ Site::text('home.college_card_link') }} <span aria-hidden="true">→</span></span>@endif
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </section>
                @endif
                @break

            @case('recognitions')
                @if ($recognitions)
                    <section class="ac-section" id="recognitions" aria-labelledby="recognitions-heading">
                        <div class="ac-container">
                            @include('academics.partials.section-head', ['section' => $section, 'id' => 'recognitions-heading'])
                            @include('academics.partials.recognitions', ['items' => $recognitions])
                        </div>
                    </section>
                @endif
                @break

            @case('featured')
                @if ($featured->isNotEmpty())
                    <section class="ac-section ac-section-tint" aria-labelledby="featured-heading">
                        <div class="ac-container">
                            @include('academics.partials.section-head', ['section' => $section, 'id' => 'featured-heading', 'link' => route('academics.programs')])
                            <div class="ac-card-grid">
                                @foreach ($featured as $program)
                                    @include('academics.partials.program-card')
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif
                @break

            @case('news')
                @if ($news->isNotEmpty() || $events->isNotEmpty())
                    <section class="ac-section" aria-labelledby="updates-heading">
                        <div class="ac-container">
                            @include('academics.partials.section-head', ['section' => $section, 'id' => 'updates-heading', 'link' => route('news.index')])
                            <div class="ac-updates {{ $news->isNotEmpty() && $events->isNotEmpty() ? 'has-events' : '' }}">
                                @if ($news->isNotEmpty())
                                    <div class="ac-news-grid">
                                        @foreach ($news as $story)
                                            @include('partials.news-card')
                                        @endforeach
                                    </div>
                                @endif
                                @if ($events->isNotEmpty())
                                    <aside aria-label="{{ $section['events_heading'] ?? '' }}">
                                        @if (! empty($section['events_heading']))<h3 class="ac-subhead">{{ $section['events_heading'] }}</h3>@endif
                                        @include('academics.partials.events', ['showCollege' => true])
                                    </aside>
                                @endif
                            </div>
                        </div>
                    </section>
                @endif
                @break

            @case('cta')
                @include('academics.partials.enroll')
                @break
        @endswitch
    @endforeach
</main>
@endsection
