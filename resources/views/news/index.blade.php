@extends('layouts.app')

@use('App\Support\Site')

@section('title', Site::text('news.meta_title') ?: Site::text('news.heading'))

@push('base-styles')
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/university.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/university-v3.css') }}">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/news-card.css') }}">
@endpush

@section('content')
@php
    $filtered = request()->filled('search') || request()->filled('category') || request()->filled('college') || request()->filled('sdg');
    $label = fn (string $key, array $replace = []) => Site::text("news.labels.$key", $replace);
    $categories = ['' => $label('all_categories')] + \App\Models\Term::options('news_category');
    $college = request()->filled('college') ? \App\Models\College::where('slug', request('college'))->first() : null;
    $sdg = \App\Support\Sdg::name(request()->integer('sdg'));
    // Filter links keep the other active filters (search, category, college, goal) and start again at page 1.
    $filter = fn (array $change) => route('news.index', array_filter(array_merge(request()->only(['search', 'category', 'college', 'sdg']), $change)));
@endphp

<section class="page-banner">
    <div class="page-banner-inner">
        <nav class="page-breadcrumb" aria-label="{{ \App\Support\Site::text('a11y.breadcrumb') }}">
            <a href="{{ route('academics.index') }}">{{ Site::text('header.brand_label') ?: Site::text('contact.site_name') }}</a>
            <span aria-hidden="true">/</span>
            <span>{{ $label('breadcrumb') }}</span>
        </nav>
        <h1>{{ Site::text('news.heading') }}</h1>
        <p class="page-banner-lead">
            @if ($college || $sdg)
                {{ $college ? $label('filtered_college', ['college' => $college->name]) : '' }}{{ $college && $sdg ? ' · ' : '' }}{{ $sdg ? $label('filtered_sdg', ['number' => request()->integer('sdg'), 'goal' => $sdg]) : '' }}.
                <a href="{{ route('news.index') }}" style="color: inherit">{{ $label('see_all') }}</a>
            @else
                {{ Site::text('news.intro') }}
            @endif
        </p>
    </div>
</section>

<main class="news-index">
    <div class="home-container">

        {{-- Search, category chips and SDG filters; each keeps the other active filters --}}
        <div class="news-toolbar">
            <form action="{{ route('news.index') }}" method="GET" class="news-search" role="search">
                <label for="news-search" class="sr-only">{{ $label('search_label') }}</label>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" id="news-search" name="search"
                       value="{{ request('search') }}"
                       placeholder="{{ $label('search_placeholder') }}">
                @foreach (['category', 'college', 'sdg'] as $keep)
                    @if (request()->filled($keep))
                        <input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">
                    @endif
                @endforeach
                <button type="submit" class="home-btn home-btn-solid">{{ $label('search_button') }}</button>
            </form>

            <nav class="news-chips" aria-label="{{ $label('search_label') }}">
                @foreach ($categories as $value => $categoryLabel)
                    <a href="{{ $filter(['category' => $value]) }}"
                       class="news-chip"
                       @if ((string) request('category') === (string) $value) aria-current="page" @endif>
                        {{ $categoryLabel }}
                    </a>
                @endforeach
            </nav>

            <nav class="news-sdg-filter" aria-label="{{ $label('sdg_filter') }}">
                <a href="{{ $filter(['sdg' => null]) }}" class="news-sdg-chip is-all" @unless ($sdg) aria-current="page" @endunless>{{ $label('all_sdgs') }}</a>
                @foreach (\App\Support\Sdg::goals() as $n => [$goalName, $color, $ink, $tint, $tintInk])
                    <a href="{{ $filter(['sdg' => $n]) }}" class="news-sdg-chip"
                       style="--sdg: {{ $color }}; --sdg-ink: {{ $ink }}; --sdg-tint: {{ $tint }}; --sdg-tint-ink: {{ $tintInk }}"
                       title="{{ $goalName }}"
                       @if (request()->integer('sdg') === $n) aria-current="page" @endif>{{ Site::text('sdgs.labels.goal_kicker', ['number' => $n]) }}<span class="sr-only">: {{ $goalName }}</span></a>
                @endforeach
            </nav>
        </div>

        <div class="news-front {{ $latestNews->isNotEmpty() ? 'has-aside' : '' }}">
            <div class="news-front-main">
                @if ($featuredNews)
                    <section class="news-front-section" aria-labelledby="news-featured-heading">
                        <h2 class="news-section-label" id="news-featured-heading">{{ $label('featured_heading') }}</h2>

                        <article class="news-feature news-feature-hero">
                            <a href="{{ route('news.show', $featuredNews->slug) }}" class="news-feature-media" tabindex="-1" aria-hidden="true">
                                @if ($featuredNews->featured_image)
                                    <img src="{{ \App\Support\Thumb::url($featuredNews->featured_image, 960) }}" srcset="{{ \App\Support\Thumb::srcset($featuredNews->featured_image) }}" sizes="(max-width: 1099px) 92vw, 820px" alt="" fetchpriority="high" decoding="async">
                                @else
                                    <span class="news-media-fallback"><img src="{{ \App\Support\Site::logo() }}" alt=""></span>
                                @endif
                            </a>

                            <div class="news-feature-body">
                                <div class="news-meta">
                                    <span class="news-category">{{ \App\Models\Term::label('news_category', $featuredNews->category) }}</span>
                                    <time datetime="{{ ($featuredNews->published_at ?? $featuredNews->created_at)?->toDateString() }}">{{ $featuredNews->displayDate() }}</time>
                                </div>

                                <h3><a href="{{ route('news.show', $featuredNews->slug) }}">{{ $featuredNews->title }}</a></h3>

                                @if ($featuredNews->excerpt)
                                    <p class="news-lead">{{ $featuredNews->excerpt }}</p>
                                @else
                                    <p class="news-summary">{{ $featuredNews->summary(220) }}</p>
                                @endif

                                @include('partials.news-sdgs', ['sdgs' => $featuredNews->sdgs, 'max' => 3])

                                <a href="{{ route('news.show', $featuredNews->slug) }}" class="news-read">
                                    {{ $label('read_full') }} <span aria-hidden="true">→</span><span class="sr-only">: {{ $featuredNews->title }}</span>
                                </a>
                            </div>
                        </article>
                    </section>
                @endif

                <section class="news-front-section" aria-labelledby="news-list-heading">
                    <h2 class="news-index-heading" id="news-list-heading">
                        {{ $filtered ? $label('results_heading') : $label('list_heading') }}
                    </h2>

                    @if ($news->isNotEmpty())
                        {{-- A grid on wider screens; on phones a sideways swipe rail (keyboard: focus it, then arrow keys) --}}
                        <div class="news-cards news-rail" tabindex="0" role="region" aria-labelledby="news-list-heading">
                            @foreach ($news as $story)
                                @include('partials.news-card', ['story' => $story])
                            @endforeach
                        </div>

                        {{ $news->onEachSide(1)->links('pagination::default') }}
                    @else
                        <div class="news-empty-state">
                            <h3>{{ $label('empty_heading') }}</h3>
                            <p>{{ $label('empty_text') }}</p>
                            @if ($filtered)
                                <a href="{{ route('news.index') }}" class="home-btn home-btn-outline">{{ $label('view_all') }}</a>
                            @endif
                        </div>
                    @endif
                </section>
            </div>

            @if ($latestNews->isNotEmpty())
                <aside class="news-front-aside" aria-labelledby="news-latest-heading">
                    <h2 class="news-index-heading" id="news-latest-heading">{{ $label('latest_heading') }}</h2>
                    <div class="news-rows">
                        @foreach ($latestNews as $story)
                            @include('partials.news-card', ['story' => $story, 'variant' => 'row'])
                        @endforeach
                    </div>
                </aside>
            @endif
        </div>
    </div>
</main>
@endsection
