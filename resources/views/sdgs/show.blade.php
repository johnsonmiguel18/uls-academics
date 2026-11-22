@extends('layouts.app')

@use('App\Support\Site')

@php
    [$name, $color, $ink] = $goalInfo;
    $label = fn (string $key, array $replace = []) => Site::text("sdgs.labels.$key", ['number' => $goal, 'goal' => $name] + $replace);
@endphp

@section('title', "SDG $goal: $name | " . Site::text('contact.site_name'))

{{-- One goal: its aim (Lists and categories → SDGs), the colleges, extension projects and stories tagged with it. --}}
@include('academics.partials.seo', [
    'title' => "SDG $goal: $name | " . Site::text('contact.university_short_name'),
    'description' => \Illuminate\Support\Str::limit($description ?: Site::text('sdgs.intro'), 160),
])

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/academics-college.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/academics-sdgs.css') }}">
    @if ($stories->isNotEmpty())<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/news-card.css') }}">@endif
@endpush

@section('content')
<main class="ac-page">
    <section class="ac-hero ac-hero-compact">
        <div class="ac-container ac-sdg-hero" style="--sdg: {{ $color }}; --sdg-ink: {{ $ink }}">
            <div>
                <nav class="ac-breadcrumb" aria-label="{{ Site::text('a11y.breadcrumb') }}">
                    <a href="{{ route('academics.index') }}">{{ Site::text('header.brand_label') ?: Site::text('contact.site_name') }}</a>
                    <a href="{{ route('sdgs.index') }}">{{ $label('breadcrumb') }}</a>
                    <span aria-current="page">{{ $name }}</span>
                </nav>
                <p class="ac-kicker">{{ $label('goal_kicker') }}</p>
                <h1>{{ $name }}</h1>
                @if ($description)<p class="ac-lead">{{ $description }}</p>@endif
            </div>
            <span class="ac-sdg-badge" aria-hidden="true">{{ $goal }}</span>
        </div>
    </section>

    @if ($involved->isEmpty() && $stories->isEmpty())
        <section class="ac-section"><div class="ac-container"><p class="ac-muted">{{ $label('goal_empty') }}</p></div></section>
    @endif

    @if ($involved->isNotEmpty() || $projects->isNotEmpty())
        <section class="ac-section" aria-labelledby="work-heading">
            <div class="ac-container ac-research-grid">
                @if ($involved->isNotEmpty())
                    <div>
                        @include('academics.partials.section-head', ['section' => ['heading' => $label('goal_colleges_heading')], 'id' => 'work-heading'])
                        <ul class="ac-sdg-colleges">
                            @foreach ($involved as $college)
                                <li>
                                    <a href="{{ route('news.index', ['college' => $college->slug, 'sdg' => $goal]) }}">{{ $college->name }}</a>
                                    <span class="ac-muted">{{ $label('goal_college_count', ['count' => $collegeCounts[$college->id]]) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($projects->isNotEmpty())
                    <div>
                        <h2 @if ($involved->isEmpty()) id="work-heading" @endif>{{ $label('goal_projects_heading') }}</h2>
                        <ul class="ac-projects">
                            @foreach ($projects as $item)
                                @php($project = $item['project'])
                                <li>
                                    <h3>{{ $project['title'] ?? '' }}</h3>
                                    <p>{{ $project['description'] ?? '' }}@if (! empty($project['year'])) ({{ $project['year'] }})@endif</p>
                                    <div class="ac-project-foot">
                                        <a href="{{ route('academics.college', $item['college']) }}#sdgs">{{ $item['college']->short_name ?: $item['college']->name }}</a>
                                        @if (! empty($project['url']))<a href="{{ $project['url'] }}" rel="noopener">{{ $label('read_more') }} <span aria-hidden="true">→</span></a>@endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if ($stories->isNotEmpty())
        <section class="ac-section ac-section-tint" aria-labelledby="stories-heading">
            <div class="ac-container">
                @include('academics.partials.section-head', [
                    'section' => ['heading' => $label('goal_stories_heading'), 'link_label' => $storyCount > $stories->count() ? $label('goal_stories_all', ['count' => $storyCount]) : ''],
                    'id' => 'stories-heading',
                    'link' => route('news.index', ['sdg' => $goal]),
                ])
                {{-- The same count, in the same words, as the news page filtered to this goal --}}
                <p class="ac-muted ac-sdg-story-count">{{ \App\Support\Sdg::countText('story', $storyCount) }}</p>
                <div class="ac-news-grid">
                    @foreach ($stories as $story)
                        @include('partials.news-card')
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <nav class="ac-section ac-sdg-pager" aria-label="{{ $label('breadcrumb') }}">
        <div class="ac-container">
            @if ($previous)<a href="{{ route('sdgs.show', $previous) }}"><span aria-hidden="true">←</span> {{ $label('previous') }}: {{ $previous }}</a>@else<span></span>@endif
            <a href="{{ route('sdgs.index') }}">{{ $label('all_goals') }}</a>
            @if ($next)<a href="{{ route('sdgs.show', $next) }}">{{ $label('next') }}: {{ $next }} <span aria-hidden="true">→</span></a>@else<span></span>@endif
        </div>
    </nav>
</main>
@endsection
