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
    $goals = \App\Support\Sdg::goals();
    $active = $goals[request()->integer('sdg')] ?? null; // the chosen goal, shown on the "Goals" button
    // CNN-style lead: under the featured story, the next three stories as text-only headlines;
    // the cards continue with the rest of the page (6 of the default 9: two full rows of three).
    // Only with at least 6 stories on the page, so cards always remain.
    $related = $featuredNews && $news->count() >= 6 ? $news->getCollection()->take(3) : collect();
    $cards = $news->getCollection()->slice($related->count());
@endphp

<main class="news-index">
{{-- Compact page header, so the lead story shows without scrolling (CNN-style).
     Inside <main>, so screen readers jumping to the main content start at the page title. --}}
<section class="news-masthead">
    <div class="home-container news-masthead-inner">
        <nav class="page-breadcrumb" aria-label="{{ \App\Support\Site::text('a11y.breadcrumb') }}">
            <a href="{{ route('academics.index') }}">{{ Site::text('header.brand_label') ?: Site::text('contact.site_name') }}</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">{{ $label('breadcrumb') }}</span>
        </nav>
        <h1>{{ Site::text('news.heading') }}</h1>
        {{-- Only on filtered lists: which college or goal is shown, and a way back --}}
        @if ($college || $sdg)
            <p class="news-masthead-lead">
                {{ $college ? $label('filtered_college', ['college' => $college->name]) : '' }}{{ $college && $sdg ? ' · ' : '' }}{{ $sdg ? $label('filtered_sdg', ['number' => request()->integer('sdg'), 'goal' => $sdg]) : '' }}.
                <a href="{{ route('news.index') }}">{{ $label('see_all') }}</a>
            </p>
        @endif
    </div>
</section>

    <div class="home-container">

        {{-- One row: search, category tabs, and the 17 goals in a "Goals" menu; each keeps the other active filters --}}
        <div class="news-toolbar">
            <form action="{{ route('news.index') }}" method="GET" class="news-search" role="search" aria-label="{{ $label('search_label') }}">
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

            <nav class="news-chips" aria-label="{{ $label('categories_label') }}">
                @foreach ($categories as $value => $categoryLabel)
                    <a href="{{ $filter(['category' => $value]) }}"
                       class="news-chip"
                       @if ((string) request('category') === (string) $value) aria-current="page" @endif>
                        {{ $categoryLabel }}
                    </a>
                @endforeach
            </nav>

            {{-- A native disclosure: opens and closes without JavaScript --}}
            <details class="news-sdg-menu">
                <summary class="news-sdg-menu-toggle {{ $active ? 'is-active' : '' }}"
                         @if ($active) style="--sdg: {{ $active[1] }}; --sdg-ink: {{ $active[2] }}" @endif>
                    {{ $active ? Site::text('sdgs.labels.goal_kicker', ['number' => request()->integer('sdg')]) : $label('sdg_menu') }}
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </summary>
                <nav class="news-sdg-filter" aria-label="{{ $label('sdg_filter') }}">
                    <a href="{{ $filter(['sdg' => null]) }}" class="news-sdg-chip is-all" @unless ($sdg) aria-current="page" @endunless>{{ $label('all_sdgs') }}</a>
                    @foreach ($goals as $n => [$goalName, $color, $ink, $tint, $tintInk])
                        <a href="{{ $filter(['sdg' => $n]) }}" class="news-sdg-chip"
                           style="--sdg: {{ $color }}; --sdg-ink: {{ $ink }}; --sdg-tint: {{ $tint }}; --sdg-tint-ink: {{ $tintInk }}"
                           @if (request()->integer('sdg') === $n) aria-current="page" @endif>{{ Site::text('sdgs.labels.goal_kicker', ['number' => $n]) }}<span class="sr-only">: {{ $goalName }}</span></a>
                    @endforeach
                </nav>
            </details>
        </div>


        <div class="news-front {{ $latestNews->isNotEmpty() ? 'has-aside' : '' }}">
            <div class="news-front-main">
                @if ($featuredNews)
                    <section class="news-front-section" aria-labelledby="news-featured-heading">
                        <h2 class="sr-only" id="news-featured-heading">{{ $label('featured_heading') }}</h2>

                        <article class="news-feature news-lead-story">
                            <div class="news-lead-head">
                                <div class="news-meta">
                                    <span class="news-category">{{ \App\Models\Term::label('news_category', $featuredNews->category) }}</span>
                                    <time datetime="{{ ($featuredNews->published_at ?? $featuredNews->created_at)?->toDateString() }}">{{ $featuredNews->displayDate() }}</time>
                                </div>
                                <h3><a href="{{ route('news.show', $featuredNews->slug) }}">{{ $featuredNews->title }}</a></h3>
                            </div>

                            <a href="{{ route('news.show', $featuredNews->slug) }}" class="news-feature-media" tabindex="-1" aria-hidden="true">
                                @if ($featuredNews->featured_image)
                                    <img src="{{ \App\Support\Thumb::url($featuredNews->featured_image, 960) }}" srcset="{{ \App\Support\Thumb::srcset($featuredNews->featured_image) }}" sizes="(max-width: 1099px) 92vw, 540px" alt="" fetchpriority="high" decoding="async">
                                @else
                                    <span class="news-media-fallback"><img src="{{ \App\Support\Site::logo() }}" alt=""></span>
                                @endif
                            </a>

                            <div class="news-lead-body">
                                <p class="news-summary">{{ $featuredNews->summary(220) }}</p>

                                @include('partials.news-sdgs', ['sdgs' => $featuredNews->sdgs, 'max' => 3])
                            </div>

                            {{-- The next three stories as plain headlines, in a row under the lead (CNN-style) --}}
                            @if ($related->isNotEmpty())
                                <ul class="news-lead-related" aria-label="{{ $label('related_heading') }}">
                                    @foreach ($related as $story)
                                        <li><a href="{{ route('news.show', $story->slug) }}">{{ $story->title }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </article>
                    </section>
                @endif

                <section class="news-front-section" aria-labelledby="news-list-heading">
                    <h2 class="news-index-heading" id="news-list-heading">
                        {{ $filtered ? $label('results_heading') : $label('list_heading') }}
                    </h2>

                    @if ($cards->isNotEmpty())
                        {{-- A grid on wider screens; on phones a sideways swipe rail (keyboard: focus it, then arrow keys).
                             On phones the site script shows arrow buttons and dots while there is more to swipe. --}}
                        <div class="news-rail-wrap">
                            <div class="news-cards news-rail" id="news-rail" tabindex="0" role="group" aria-labelledby="news-list-heading">
                                @foreach ($cards as $story)
                                    @include('partials.news-card', ['story' => $story])
                                @endforeach
                            </div>
                            <button type="button" class="news-rail-arrow is-prev" data-rail="prev" aria-controls="news-rail" aria-label="{{ $label('rail_previous') }}" hidden>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            </button>
                            <button type="button" class="news-rail-arrow is-next" data-rail="next" aria-controls="news-rail" aria-label="{{ $label('rail_next') }}" hidden>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                            <div class="news-rail-dots" aria-hidden="true" hidden></div>
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
