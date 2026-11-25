@extends('layouts.app')

@use('App\Support\Prose')
@use('App\Support\Site')
@use('Illuminate\Support\Str')

@section('title', $program->name . ' | ' . $program->college->name . ' | ' . Site::text('contact.site_name'))

{{-- Section order, headings and labels: Site settings → Program pages. Content: the program record. --}}
@php
    $college = $program->college;
    $replace = ['college' => $college->name, 'short' => $college->short_name ?: $college->name, 'program' => $program->name];
    $label = fn (string $key) => Site::text("program.labels.$key", $replace);
    $hasContent = [
        'overview' => (bool) $program->overview,
        'objectives' => (bool) $program->objectives,
        'curriculum' => $program->curriculum || $program->curriculum_file,
        'careers' => (bool) $program->careers,
        // Requirements live on the University's official admission page (deans, November 2026): a button, not a copy.
        'admission' => Site::text('contact.admission_url') && $label('admission_button'),
        'accreditation' => $program->accreditations->isNotEmpty(),
        'faqs' => (bool) $program->faqs,
    ];
    $shown = array_filter($sections, fn ($section, $type) => $hasContent[$type] ?? false, ARRAY_FILTER_USE_BOTH);
    $facts = array_filter([
        $label('fact_degree') => $program->degreeLabel(),
        $label('fact_level') => \App\Models\Term::label('level', $program->level),
        $label('fact_duration') => $program->duration,
        $label('fact_code') => $program->code,
        $label('fact_accreditation') => $program->accreditations->first()?->label(),
    ], fn ($value, $key) => $key !== '' && filled($value), ARRAY_FILTER_USE_BOTH);
    $description = $program->meta_description ?: Str::limit($program->summary ?: strip_tags(Prose::html($program->overview)) ?: Site::text('seo.default_description'), 160);
    $image = $program->image ?: $college->banner_image;
    $enrollUrl = Site::text('contact.enroll_url');
@endphp

@include('academics.partials.seo', [
    'title' => $program->name . ' | ' . Site::text('contact.university_short_name'),
    'description' => $description,
    'image' => $image,
    'schema' => array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'EducationalOccupationalProgram',
        'name' => $program->name,
        'description' => $description,
        'url' => route('academics.program', $program),
        'educationalCredentialAwarded' => $program->degreeLabel(),
        'timeToComplete' => $program->duration,
        'provider' => ['@type' => 'CollegeOrUniversity', 'name' => Site::text('contact.university_name'), 'url' => Site::text('contact.main_site_url')],
    ]),
])

@push('styles')
    @if ($image)<link rel="preload" as="image" href="{{ \App\Support\Thumb::url($image, 960) }}" fetchpriority="high">@endif
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/academics-program.css') }}">
@endpush

@section('content')
@if ($image)
    @push('inline-styles'){!! \App\Support\Theme::url('.ac-hero-split', '--ac-hero-image', \App\Support\Thumb::url($image, 960)) !!}@endpush
@endif
<main class="ac-page ac-page-college college-{{ $college->id }}">
    <section class="ac-hero ac-hero-split">
        <div class="ac-container ac-hero-grid">
            <div>
                <nav class="ac-breadcrumb" aria-label="{{ \App\Support\Site::text('a11y.breadcrumb') }}">
                    <a href="{{ route('academics.index') }}">{{ Site::text('header.brand_label') ?: Site::text('contact.site_name') }}</a>
                    <a href="{{ route('academics.college', $college) }}">{{ $college->name }}</a>
                    <span aria-current="page">{{ $program->code ?: $program->name }}</span>
                </nav>
                <p class="ac-kicker">
                    <a href="{{ route('academics.college', $college) }}">{{ $college->name }}</a>
                    @if ($program->department) · <a href="{{ route('academics.department', [$college, $program->department]) }}">{{ $program->department->name }}</a>@endif
                </p>
                <h1>{{ $program->name }}</h1>
                @if ($program->summary)<p class="ac-lead">{{ $program->summary }}</p>@endif
                @if ($program->majors)
                    <ul class="ac-chips" aria-label="{{ Site::text('college.labels.majors') }}">
                        @foreach (preg_split('/\s*,\s*|\s+and\s+/', $program->majors) as $major)
                            @if (trim($major) !== '')<li>{{ trim($major) }}</li>@endif
                        @endforeach
                    </ul>
                @endif
                <div class="ac-hero-actions">
                    @if ($enrollUrl && $label('apply'))
                        <a class="ac-button ac-button-light" href="{{ $enrollUrl }}" rel="noopener">{{ $label('apply') }} <span aria-hidden="true">↗</span></a>
                    @endif
                    @if ($program->curriculum_file && $label('download_curriculum'))
                        <a class="ac-button ac-button-outline-light" href="{{ asset('storage/' . $program->curriculum_file) }}" target="_blank" rel="noopener">{{ $label('download_curriculum') }}</a>
                    @endif
                </div>
            </div>

            @if ($facts)
                <aside class="ac-facts-card" aria-label="{{ $program->name }}">
                    <dl>
                        @foreach ($facts as $factLabel => $value)
                            <div><dt>{{ $factLabel }}</dt><dd>{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                </aside>
            @endif
        </div>
    </section>

    <div class="ac-container ac-program-layout">
        @if (count($shown) > 1)
            <nav class="ac-toc" aria-label="{{ $label('on_this_page') }}">
                <p class="ac-kicker">{{ $label('on_this_page') }}</p>
                @foreach ($shown as $type => $section)
                    <a href="#{{ $type }}">{{ Site::fill($section['nav_label'] ?? $section['heading'] ?? $type, $replace) }}</a>
                @endforeach
            </nav>
        @endif

        <div class="ac-program-content">
            @if (! $shown)
                <div class="ac-empty">
                    <h2>{{ $label('empty_heading') }}</h2>
                    <p>{{ $label('empty_text') }}</p>
                </div>
            @endif

            @foreach ($shown as $type => $section)
                <section id="{{ $type }}" class="ac-block">
                    <h2>{{ Site::fill($section['heading'] ?? '', $replace) }}</h2>
                    @switch($type)
                        @case('overview')<div class="ac-prose">{!! Prose::html($program->overview) !!}</div>@break
                        @case('objectives')<div class="ac-prose">{!! Prose::html($program->objectives) !!}</div>@break
                        @case('careers')<div class="ac-prose">{!! Prose::html($program->careers) !!}</div>@break
                        @case('admission')
                            <div class="ac-admission">
                                @if ($label('admission_text'))<p>{{ $label('admission_text') }}</p>@endif
                                <a class="ac-button" href="{{ Site::text('contact.admission_url') }}" rel="noopener">{{ $label('admission_button') }} <span aria-hidden="true">↗</span></a>
                            </div>
                            @break
                        @case('curriculum')
                            @if ($program->curriculum)<div class="ac-prose">{!! Prose::html($program->curriculum) !!}</div>@endif
                            @if ($program->curriculum_file && $label('download_curriculum_full'))
                                <a class="ac-button ac-button-ghost" href="{{ asset('storage/' . $program->curriculum_file) }}" target="_blank" rel="noopener">{{ $label('download_curriculum_full') }}</a>
                            @endif
                            @break
                        @case('accreditation')
                            <ul class="ac-accreditations">
                                @foreach ($program->accreditations as $accreditation)
                                    <li>
                                        <strong>{{ $accreditation->label() }}</strong>
                                        <span>
                                            @if ($accreditation->valid_until){{ $label('valid_until') }} {{ $accreditation->valid_until->format('F Y') }}@endif
                                            @if ($accreditation->notes) · {{ $accreditation->notes }}@endif
                                            @if ($accreditation->certificate) · <a href="{{ asset('storage/' . $accreditation->certificate) }}" target="_blank" rel="noopener">{{ $label('certificate') }}</a>@endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                            @break
                        @case('faqs')
                            @foreach ($program->faqs as $faq)
                                <details class="ac-details">
                                    <summary>{{ $faq['question'] ?? '' }}</summary>
                                    <p>{!! nl2br(e($faq['answer'] ?? '')) !!}</p>
                                </details>
                            @endforeach
                            @break
                    @endswitch
                </section>
            @endforeach
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="ac-section ac-section-tint" aria-labelledby="related-heading">
            <div class="ac-container">
                @include('academics.partials.section-head', [
                    'id' => 'related-heading',
                    'section' => ['kicker' => $label('related_kicker'), 'heading' => $label('related_heading'), 'link_label' => $label('related_link')],
                    'link' => route('academics.college', $college) . '#programs',
                ])
                <div class="ac-card-grid">
                    @foreach ($related as $item)
                        @include('academics.partials.program-card', ['program' => $item, 'showCollege' => false])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('academics.partials.enroll', ['heading' => $label('cta_heading')])
</main>
@endsection
