@extends('layouts.app')

@use('App\Support\Prose')
@use('App\Support\Site')
@use('Illuminate\Support\Str')

@section('title', $department->name . ' | ' . $college->name . ' | ' . Site::text('contact.site_name'))

@php
    $label = fn (string $key) => Site::text("department.labels.$key", ['college' => $college->name]);
    $accreditations = $department->programs->flatMap(fn ($p) => $p->accreditations->map(fn ($a) => [$p, $a]));
@endphp

@include('academics.partials.seo', [
    'title' => $department->name . ' | ' . Site::text('contact.university_short_name'),
    'description' => Str::limit($department->overview ?: Site::text('seo.default_description'), 160),
    'image' => $college->banner_image,
])

@section('content')
<main class="ac-page">
    <section class="ac-hero ac-hero-compact">
        <div class="ac-container">
            <nav class="ac-breadcrumb" aria-label="{{ \App\Support\Site::text('a11y.breadcrumb') }}">
                <a href="{{ route('academics.index') }}">{{ Site::text('header.brand_label') ?: Site::text('contact.site_name') }}</a>
                <a href="{{ route('academics.college', $college) }}">{{ $college->name }}</a>
                <span aria-current="page">{{ $department->name }}</span>
            </nav>
            <p class="ac-kicker">{{ $college->name }}</p>
            <h1>{{ $department->name }}</h1>
        </div>
    </section>

    @if ($department->overview)
        <section class="ac-section" aria-label="{{ $department->name }}">
            <div class="ac-container"><div class="ac-prose ac-prose-lead">{!! Prose::html($department->overview) !!}</div></div>
        </section>
    @endif

    @if ($department->programs->isNotEmpty())
        <section class="ac-section ac-section-tint" aria-labelledby="dept-programs">
            <div class="ac-container">
                <header class="ac-section-head"><div><p class="ac-kicker">{{ $label('programs_kicker') }}</p><h2 id="dept-programs">{{ $label('programs_heading') }}</h2></div></header>
                <div class="ac-card-grid">
                    @foreach ($department->programs as $program)
                        @include('academics.partials.program-card', ['showCollege' => false])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($department->faculty->isNotEmpty())
        <section class="ac-section" aria-labelledby="dept-faculty">
            <div class="ac-container">
                <header class="ac-section-head"><div><p class="ac-kicker">{{ $label('faculty_kicker') }}</p><h2 id="dept-faculty">{{ $label('faculty_heading') }}</h2></div></header>
                <ul class="ac-people">
                    @foreach ($department->faculty as $person)
                        @include('academics.partials.person')
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if ($department->achievements || $accreditations->isNotEmpty())
        <section class="ac-section ac-section-tint" aria-label="{{ $label('achievements_heading') }}">
            <div class="ac-container ac-two-col">
                @if ($department->achievements)
                    <div>
                        <p class="ac-kicker">{{ $label('achievements_kicker') }}</p>
                        <h2>{{ $label('achievements_heading') }}</h2>
                        <div class="ac-prose">{!! Prose::html($department->achievements) !!}</div>
                    </div>
                @endif
                @if ($accreditations->isNotEmpty())
                    <div>
                        <p class="ac-kicker">{{ $label('accreditation_kicker') }}</p>
                        <h2>{{ $label('accreditation_heading') }}</h2>
                        <ul class="ac-accreditations">
                            @foreach ($accreditations as [$program, $accreditation])
                                <li><strong>{{ $accreditation->label() }}</strong><span>{{ $program->name }}@if ($accreditation->valid_until) · {{ Site::text('program.labels.valid_until') }} {{ $accreditation->valid_until->format('F Y') }}@endif</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if ($department->documents->isNotEmpty())
        <section class="ac-section" aria-labelledby="dept-resources">
            <div class="ac-container">
                <header class="ac-section-head"><div><p class="ac-kicker">{{ $label('resources_kicker') }}</p><h2 id="dept-resources">{{ $label('resources_heading') }}</h2></div></header>
                @include('academics.partials.documents', ['documents' => $department->documents])
            </div>
        </section>
    @endif

    @include('academics.partials.enroll')
</main>
@endsection
