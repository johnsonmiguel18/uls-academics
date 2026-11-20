@extends('layouts.app')

@use('App\Models\Term')
@use('App\Support\Site')

@section('title', Site::text('finder.meta_title') ?: Site::text('finder.heading'))

@include('academics.partials.seo', [
    'title' => Site::text('finder.meta_title') ?: Site::text('finder.heading'),
    'description' => Site::text('finder.meta_description') ?: Site::text('seo.default_description'),
])

@push('styles')<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/academics-finder.css') }}">@endpush

@section('content')
{{-- Labels: Site settings → Finder, news, 404. Filter choices: Admin → Lists and categories. --}}
@php
    $active = array_filter($filters);
    $label = fn (string $key, array $replace = []) => Site::text("finder.labels.$key", $replace);
@endphp
<main class="ac-page">
    <section class="ac-hero ac-hero-compact">
        <div class="ac-container">
            <nav class="ac-breadcrumb" aria-label="{{ \App\Support\Site::text('a11y.breadcrumb') }}">
                <a href="{{ route('academics.index') }}">{{ Site::text('header.brand_label') ?: Site::text('contact.site_name') }}</a>
                <span aria-current="page">{{ Site::text('finder.heading') }}</span>
            </nav>
            @if (Site::text('finder.kicker'))<p class="ac-kicker">{{ Site::text('finder.kicker') }}</p>@endif
            <h1>{{ Site::text('finder.heading') }}</h1>
        </div>
    </section>

    <section class="ac-section ac-section-flush">
        <div class="ac-container">
            {{-- Server-side filtering and paging: plain GET form, so every result list has its own shareable, crawlable URL. --}}
            <form class="ac-filters" action="{{ route('academics.programs') }}" method="get" role="search" data-autosubmit>
                <div class="ac-field ac-field-wide">
                    <label for="f-q">{{ $label('search') }}</label>
                    <input id="f-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ $label('search_placeholder') }}">
                </div>
                <div class="ac-field">
                    <label for="f-level">{{ $label('level') }}</label>
                    <select id="f-level" name="level">
                        <option value="">{{ $label('level_all') }}</option>
                        @foreach (Term::options('level') as $value => $text)
                            <option value="{{ $value }}" @selected(($filters['level'] ?? '') === $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ac-field">
                    <label for="f-college">{{ $label('college') }}</label>
                    <select id="f-college" name="college">
                        <option value="">{{ $label('college_all') }}</option>
                        @foreach ($colleges as $college)
                            <option value="{{ $college->slug }}" @selected(($filters['college'] ?? '') === $college->slug)>{{ $college->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ac-field">
                    <label for="f-degree">{{ $label('degree') }}</label>
                    <select id="f-degree" name="degree">
                        <option value="">{{ $label('degree_all') }}</option>
                        @foreach (Term::options('degree_type') as $value => $text)
                            <option value="{{ $value }}" @selected(($filters['degree'] ?? '') === $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="ac-check">
                    <input type="checkbox" name="accredited" value="1" @checked($filters['accredited'] ?? false)>
                    {{ $label('accredited') }}
                </label>
                <div class="ac-filter-actions">
                    <button class="ac-button" type="submit">{{ $label('apply') }}</button>
                    @if ($active)
                        <a class="ac-button ac-button-ghost" href="{{ route('academics.programs') }}">{{ $label('clear') }}</a>
                    @endif
                </div>
            </form>

            <h2 class="ac-result-count" aria-live="polite">{{ $label('results', ['count' => $programs->total()]) }}</h2>

            <div class="ac-results">
                @if ($programs->isEmpty())
                    <div class="ac-empty">
                        <h2>{{ $label('empty_heading') }}</h2>
                        <p>{{ $label('empty_text') }} <a href="{{ route('academics.programs') }}">{{ $label('clear') }}</a></p>
                    </div>
                @else
                    <div class="ac-card-grid">
                        @foreach ($programs as $program)
                            @include('academics.partials.program-card')
                        @endforeach
                    </div>
                    <div class="ac-pagination">{{ $programs->links('pagination::default') }}</div>
                @endif
            </div>
        </div>
    </section>

    @include('academics.partials.enroll')
</main>

<script nonce="{{ Vite::cspNonce() }}">
    // Filters apply as soon as a dropdown or checkbox changes; typing still waits for Enter.
    // While the next page loads, the results fade to skeleton cards.
    const finder = document.querySelector('[data-autosubmit]');
    finder.querySelectorAll('select, input[type=checkbox]').forEach((el) => el.addEventListener('change', () => finder.requestSubmit()));
    finder.addEventListener('submit', () => document.querySelector('.ac-results')?.classList.add('is-loading'));
    document.querySelectorAll('.ac-pagination a').forEach((a) => a.addEventListener('click', () => document.querySelector('.ac-results')?.classList.add('is-loading')));
</script>
@endsection
