@extends('layouts.app')

@use('App\Support\Site')

@section('title', Site::text('news.meta_title') ?: Site::text('news.heading'))

@push('base-styles')
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/university.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/university-v3.css') }}">
@endpush

@section('content')
@php
    $filtered = request()->filled('search') || request()->filled('category') || request()->filled('college') || request()->filled('sdg');
    $label = fn (string $key, array $replace = []) => Site::text("news.labels.$key", $replace);
    $categories = ['' => $label('all_categories')] + \App\Models\Term::options('news_category');
    $college = request()->filled('college') ? \App\Models\College::where('slug', request('college'))->first() : null;
    $sdg = \App\Support\Sdg::name(request()->integer('sdg'));
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

        {{-- Search, plus category chips that keep the search term --}}
        <div class="news-toolbar">
            <form action="{{ route('news.index') }}" method="GET" class="news-search" role="search">
                <label for="news-search" class="sr-only">{{ $label('search_label') }}</label>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" id="news-search" name="search"
                       value="{{ request('search') }}"
                       placeholder="{{ $label('search_placeholder') }}">
                @if (request()->filled('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <button type="submit" class="home-btn home-btn-solid">{{ $label('search_button') }}</button>
            </form>

            <nav class="news-chips" aria-label="{{ $label('search_label') }}">
                @foreach ($categories as $value => $categoryLabel)
                    <a href="{{ route('news.index', array_filter(['category' => $value, 'search' => request('search')])) }}"
                       class="news-chip"
                       @if ((string) request('category') === $value) aria-current="page" @endif>
                        {{ $categoryLabel }}
                    </a>
                @endforeach
            </nav>
        </div>

        @if ($featuredNews)
            <article class="news-feature news-feature-wide">
                <a href="{{ route('news.show', $featuredNews->slug) }}" class="news-feature-media" tabindex="-1" aria-hidden="true">
                    @if ($featuredNews->featured_image)
                        <img src="{{ \App\Support\Thumb::url($featuredNews->featured_image, 960) }}" srcset="{{ \App\Support\Thumb::srcset($featuredNews->featured_image) }}" sizes="(max-width: 900px) 92vw, 720px" alt="" loading="lazy" decoding="async">
                    @else
                        <span class="news-media-fallback"><img src="{{ \App\Support\Site::logo() }}" alt=""></span>
                    @endif
                </a>

                <div class="news-feature-body">
                    <div class="news-meta">
                        <span class="news-category">{{ \App\Models\Term::label('news_category', $featuredNews->category) }}</span>
                        <time>{{ $featuredNews->displayDate() }}</time>
                    </div>

                    <h2><a href="{{ route('news.show', $featuredNews->slug) }}">{{ $featuredNews->title }}</a></h2>

                    @if ($featuredNews->excerpt)
                        <p class="news-lead">{{ $featuredNews->excerpt }}</p>
                    @endif

                    <p class="news-summary">{{ \Illuminate\Support\Str::limit($featuredNews->plainText(), 360) }}</p>

                    <a href="{{ route('news.show', $featuredNews->slug) }}" class="news-read">
                        Read full story <span aria-hidden="true">→</span>
                    </a>
                </div>
            </article>
        @endif

        <h2 class="news-index-heading">
            {{ $filtered ? $label('results_heading') : $label('list_heading') }}
        </h2>

        @if ($news->isNotEmpty())
            <div class="news-cards">
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
    </div>
</main>
@endsection
