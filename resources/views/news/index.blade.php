@extends('layouts.app')

@use('App\Support\Site')

@section('title', Site::text('news.meta_title') ?: Site::text('news.heading'))

@push('base-styles')
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/news.css') }}">
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
                {{ $college ? $label('filtered_college', ['college' => $college->name]) : '' }}{{ $college && $sdg ? ' · ' : '' }}{{ $sdg ? $label('filtered_sdg', ['number' => request()->integer('sdg'), 'goal' => $sdg]) : '' }}{{ $sdg ? ' · ' . \App\Support\Sdg::countText('story', $news->total()) : '' }}.
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
                <summary @class(['news-sdg-menu-toggle', 'is-active' => $active, 'sdg-' . request()->integer('sdg') => $active])>
                    {{ $active ? Site::text('sdgs.labels.goal_kicker', ['number' => request()->integer('sdg')]) : $label('sdg_menu') }}
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </summary>
                <nav class="news-sdg-filter" aria-label="{{ $label('sdg_filter') }}">
                    <a href="{{ $filter(['sdg' => null]) }}" class="news-sdg-chip is-all" @unless ($sdg) aria-current="page" @endunless>{{ $label('all_sdgs') }}</a>
                    @foreach ($goals as $n => [$goalName, $color, $ink, $tint, $tintInk])
                        <a href="{{ $filter(['sdg' => $n]) }}" class="news-sdg-chip sdg-{{ $n }}"
                           @if (request()->integer('sdg') === $n) aria-current="page" @endif>{{ Site::text('sdgs.labels.goal_kicker', ['number' => $n]) }}<span class="sr-only">: {{ $goalName }}</span></a>
                    @endforeach
                </nav>
            </details>
        </div>


        @include('partials.news-front', ['paginator' => $news, 'prefix' => 'news', 'level' => 2])
    </div>
</main>
@endsection
