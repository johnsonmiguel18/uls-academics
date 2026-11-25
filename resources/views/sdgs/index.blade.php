@extends('layouts.app')

@use('App\Support\Sdg')
@use('App\Support\Site')

@section('title', Site::text('sdgs.meta_title'))

{{-- SDG report: totals, the 17 goals with how much work is tagged with each, and the colleges behind it.
     Texts: Site settings → SDG pages. Goal names, colors and aims: Lists and categories → SDGs. --}}
@php($label = fn (string $key, array $replace = []) => Site::text("sdgs.labels.$key", $replace))

@include('academics.partials.seo', [
    'title' => Site::text('sdgs.meta_title'),
    'description' => \Illuminate\Support\Str::limit(Site::text('sdgs.intro') ?: Site::text('seo.default_description'), 160),
])

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/academics-college.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/academics-sdgs.css') }}">
@endpush

@section('content')
<main class="ac-page">
    <section class="ac-hero">
        <div class="ac-container">
            <nav class="ac-breadcrumb" aria-label="{{ Site::text('a11y.breadcrumb') }}">
                <a href="{{ route('academics.index') }}">{{ Site::text('header.brand_label') ?: Site::text('contact.site_name') }}</a>
                <span aria-current="page">{{ $label('breadcrumb') }}</span>
            </nav>
            @if (Site::text('sdgs.kicker'))<p class="ac-kicker">{{ Site::text('sdgs.kicker') }}</p>@endif
            <h1 class="ac-sdg-title">{{ Site::text('sdgs.heading') }}</h1>
            @if (Site::text('sdgs.intro'))<p class="ac-lead">{{ Site::text('sdgs.intro') }}</p>@endif
            @if (Site::text('sdgs.report_url') && Site::text('sdgs.report_label'))
                <a class="ac-button ac-button-light" href="{{ Site::text('sdgs.report_url') }}" rel="noopener">{{ Site::text('sdgs.report_label') }} <span aria-hidden="true">→</span></a>
            @endif
        </div>
    </section>

    <section class="ac-stats" aria-label="{{ Site::text('sdgs.heading') }}">
        <div class="ac-container ac-stats-grid">
            <div class="ac-stat"><strong>{{ $stats['goals'] }}</strong><span>{{ $label('goals_tracked') }}</span></div>
            <div class="ac-stat"><strong>{{ $stats['items'] }}</strong><span>{{ $label('stories_mapped') }}</span></div>
            <div class="ac-stat"><strong>{{ $stats['colleges'] }}</strong><span>{{ $label('colleges_involved') }}</span></div>
        </div>
    </section>

    <section class="ac-section" aria-labelledby="goals-heading">
        <div class="ac-container">
            @include('academics.partials.section-head', ['section' => ['heading' => $label('grid_heading')], 'id' => 'goals-heading'])
            <ul class="ac-sdg-report">
                @foreach (Sdg::goals() as $n => [$name, $color, $ink, $tint, $tintInk])
                    @php($count = $counts[$n] ?? 0)
                    <li @class(["sdg-$n", 'is-empty' => ! $count, 'is-tint' => ! $count])>
                        <a href="{{ route('sdgs.show', $n) }}">
                            <strong>{{ $n }}</strong>
                            <span class="ac-sdg-name">{{ $name }}</span>
                            <span class="ac-sdg-count">{{ $count }}<span class="sr-only"> {{ $label('stories_mapped') }}</span></span>
                            @if ($count)
                                {{-- Stories are the ones /news?sdg=N lists; projects come from the college pages --}}
                                <span class="ac-sdg-split">{{ Sdg::countText('story', $storyCounts[$n] ?? 0) }} · {{ Sdg::countText('project', $projectCounts[$n] ?? 0) }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
            @if ($label('grid_help'))<p class="ac-small ac-muted">{{ $label('grid_help') }}</p>@endif
        </div>
    </section>

    @if ($byCollege->isNotEmpty())
        <section class="ac-section ac-section-tint" aria-labelledby="by-college-heading">
            <div class="ac-container">
                @include('academics.partials.section-head', ['section' => ['heading' => $label('by_college_heading')], 'id' => 'by-college-heading'])
                <div class="ac-table-wrap">
                    <table class="ac-sdg-table">
                        <thead>
                            <tr><th scope="col">{{ $label('college_column') }}</th><th scope="col">{{ $label('goals_column') }}</th><th scope="col" class="is-num">{{ $label('items_column') }}</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($byCollege as $row)
                                <tr>
                                    <th scope="row"><a href="{{ route('academics.college', $row['college']) }}#sdgs">{{ $row['college']->name }}</a></th>
                                    <td>@include('academics.partials.sdgs', ['sdgs' => $row['goals']])</td>
                                    <td class="is-num" data-label="{{ $label('items_column') }}">{{ $row['items'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif
</main>
@endsection
