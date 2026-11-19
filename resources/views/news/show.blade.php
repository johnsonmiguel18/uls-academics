@extends('layouts.app')

@use('App\Support\Site')

@section('title', $article->title . ' | ' . Site::text('contact.site_name'))

@include('academics.partials.seo', [
    'title' => $article->title,
    'description' => \Illuminate\Support\Str::limit($article->excerpt ?: $article->plainText(), 160),
    'image' => $article->featured_image,
])

@push('base-styles')
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/university.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/university-v3.css') }}">
@endpush

@php($label = fn (string $key, array $replace = []) => Site::text("news.labels.$key", $replace))

@section('content')
<section class="page-banner article-banner">
    <div class="page-banner-inner">
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
            <p class="page-banner-lead">{{ $article->excerpt }}</p>
        @endif

        <p class="article-date">{{ $label('published', ['date' => $article->displayDate()]) }}</p>
    </div>
</section>

<main class="article-main">
    <article class="article-body {{ $article->featured_image ? 'has-image' : '' }}">
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
