@extends('layouts.app')

@use('App\Support\Site')

@section('title', $article->title . ' | ' . Site::text('contact.site_name'))

@include('academics.partials.seo', [
    'title' => $article->title,
    'description' => \Illuminate\Support\Str::limit($article->excerpt ?: $article->plainText(), 160),
    'image' => $article->featured_image,
])

@push('base-styles')
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/news.css') }}">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/news-card.css') }}">
@endpush

@php($label = fn (string $key, array $replace = []) => Site::text("news.labels.$key", $replace))

@section('content')
<main class="article-main">
{{-- Compact header like the news page: headline, summary and date, aligned with the article text --}}
<section class="news-masthead article-masthead">
    <div class="article-masthead-inner">
        <nav class="page-breadcrumb" aria-label="{{ \App\Support\Site::text('a11y.breadcrumb') }}">
            <a href="{{ route('academics.index') }}">{{ Site::text('header.brand_label') ?: Site::text('contact.site_name') }}</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('news.index') }}">{{ $label('breadcrumb') }}</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('news.index', ['category' => $article->category]) }}">
                {{ \App\Models\Term::label('news_category', $article->category) }}
            </a>
        </nav>

        <h1>{{ $article->title }}</h1>

        @if ($article->excerpt)
            <p class="article-masthead-lead">{{ $article->excerpt }}</p>
        @endif

        <p class="article-masthead-date">{{ $label('published', ['date' => $article->displayDate()]) }}</p>
    </div>
</section>

    <article class="article-body {{ $article->featured_image ? 'has-image' : '' }}">
        <div class="article-tags">
            <a class="news-category" href="{{ route('news.index', ['category' => $article->category]) }}">{{ \App\Models\Term::label('news_category', $article->category) }}</a>
            @include('partials.news-sdgs', ['sdgs' => $article->sdgs, 'max' => 17])
        </div>

        @if ($article->featured_image)
            <figure class="article-figure">
                <img src="{{ \App\Support\Thumb::url($article->featured_image, 960) }}" srcset="{{ \App\Support\Thumb::srcset($article->featured_image) }}" sizes="(max-width: 900px) 92vw, 820px" alt="{{ $article->title }}" fetchpriority="high">
            </figure>
        @endif

        {{-- The rich editor saves HTML; older plain-text articles keep their line breaks. --}}
        <div class="article-prose">
            @if ($article->content !== strip_tags($article->content))
                {!! \App\Support\Prose::html($article->content) !!}
            @else
                {!! nl2br(e($article->content)) !!}
            @endif
        </div>

        @if ($previousNews || $nextNews)
            <nav class="article-pager" aria-label="{{ $label('story_nav') }}">
                @if ($previousNews)
                    <a href="{{ route('news.show', $previousNews->slug) }}" rel="prev" class="article-pager-prev">
                        <span class="article-pager-kicker"><span aria-hidden="true">←</span> {{ $label('previous_story') }}</span>
                        <span class="article-pager-title">{{ $previousNews->title }}</span>
                    </a>
                @endif
                @if ($nextNews)
                    <a href="{{ route('news.show', $nextNews->slug) }}" rel="next" class="article-pager-next">
                        <span class="article-pager-kicker">{{ $label('next_story') }} <span aria-hidden="true">→</span></span>
                        <span class="article-pager-title">{{ $nextNews->title }}</span>
                    </a>
                @endif
            </nav>
        @endif

        <a href="{{ route('news.index') }}" class="news-read">
            <span aria-hidden="true">←</span> {{ $label('back') }}
        </a>
    </article>

    @if ($relatedNews->isNotEmpty())
        <section class="article-related" aria-labelledby="related-heading">
            <div class="home-container">
                <h2 id="related-heading">{{ $label('related_heading') }}</h2>
                <div class="news-cards">
                    @foreach ($relatedNews as $story)
                        @include('partials.news-card', ['story' => $story])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</main>
@endsection
