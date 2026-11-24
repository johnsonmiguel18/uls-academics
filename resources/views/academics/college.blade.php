@extends('layouts.app')

@section('title', $college->name . ' | ' . \App\Support\Site::text('contact.site_name'))

@use('App\Support\Prose')
@use('App\Support\Site')
@use('Illuminate\Support\Str')

{{-- Sections, their order, headings and labels come from Site settings → College pages;
     the content from the college record. A section is skipped when the college has nothing for it. --}}
@php
    $short = $college->short_name ?: $college->name;
    $replace = ['college' => $college->name, 'short' => $short, 'count' => $college->programs->count()];
    $label = fn (string $key) => Site::text("college.labels.$key", $replace);
    $lead = $college->tagline ?: Str::limit((string) ($college->overview ?: $college->mission), 220);
    $partners = collect($college->partners ?? []);
    $accredited = $college->programs->filter(fn ($program) => $program->accreditations->isNotEmpty());

    $hasContent = [
        'about' => $college->vision || $college->mission || $college->goals || $college->objectives || $college->overview || $college->video_url || $college->dean_message,
        'programs' => $college->programs->isNotEmpty(),
        'research' => $college->research_cluster || $college->research_description || $college->research_highlights || $college->focus_areas,
        'partners' => $partners->isNotEmpty() || $college->linkages,
        'students' => (bool) $college->student_benefits,
        'accreditation' => $accredited->isNotEmpty() || $recognitions,
        'extension' => (bool) $college->extension_projects,
        'faculty' => $college->relationLoaded('faculty') && $college->faculty->isNotEmpty(),
        'resources' => $college->facilities || ($college->relationLoaded('documents') && $college->documents->isNotEmpty()),
        'news' => $news->isNotEmpty() || $events->isNotEmpty(),
    ];
    $shown = array_filter($sections, fn ($section, $type) => $hasContent[$type] ?? false, ARRAY_FILTER_USE_BOTH);
    $heading = fn (array $section, string $key) => Site::fill($section[$key] ?? '', $replace);
@endphp

@include('academics.partials.seo', [
    'title' => $college->name . ' | ' . Site::text('contact.university_short_name'),
    'description' => $college->meta_description ?: Str::limit($lead ?: Site::text('seo.default_description'), 160),
    'image' => $college->banner_image,
    'schema' => [
        '@context' => 'https://schema.org',
        '@type' => 'EducationalOrganization',
        'name' => $college->name,
        'url' => route('academics.college', $college),
        'parentOrganization' => ['@type' => 'CollegeOrUniversity', 'name' => Site::text('contact.university_name'), 'url' => Site::text('contact.main_site_url')],
    ],
])

@push('styles')
    {{-- The header photo is the largest thing on screen: fetch it first. --}}
    @if ($college->banner_image)<link rel="preload" as="image" href="{{ \App\Support\Thumb::url($college->banner_image, 960) }}" fetchpriority="high">@endif
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/academics-college.css') }}">
    @isset($shown['news'])<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/news-card.css') }}">@endisset
@endpush

@section('content')
<main class="ac-page ac-page-college" style="{{ $college->accentStyle() }}; --ac-accent: {{ $college->accent() }}">
    {{-- Header: title and actions; dean and research cluster on the right --}}
    <section class="ac-hero ac-hero-split ac-hero-college" @if ($college->banner_image) style="--ac-hero-image: url('{{ \App\Support\Thumb::url($college->banner_image, 960) }}')" @endif>
        <div class="ac-container ac-hero-grid">
            <div>
                <nav class="ac-breadcrumb" aria-label="{{ \App\Support\Site::text('a11y.breadcrumb') }}">
                    <a href="{{ route('academics.index') }}">{{ Site::text('header.brand_label') ?: Site::text('contact.site_name') }}</a>
                    <span aria-current="page">{{ $college->name }}</span>
                </nav>
                @include('academics.partials.college-mark', ['size' => 72])
                <p class="ac-kicker">{{ $college->name }}</p>
                <h1>{{ $college->headline ?: $college->name }}</h1>
                @if ($lead)<p class="ac-lead">{{ $lead }}</p>@endif
                <div class="ac-hero-actions">
                    @if (isset($shown['programs']) && $label('hero_programs'))
                        <a class="ac-button ac-button-light" href="#programs">{{ $label('hero_programs') }} <span aria-hidden="true">→</span></a>
                    @endif
                    @if (isset($shown['partners']) && $label('hero_partners'))
                        <a class="ac-button ac-button-outline-light" href="#partners">{{ $label('hero_partners') }}</a>
                    @elseif (isset($shown['faculty']) && $label('hero_faculty'))
                        <a class="ac-button ac-button-outline-light" href="#faculty">{{ $label('hero_faculty') }}</a>
                    @endif
                </div>
            </div>

            @if ($college->dean_name || $college->research_cluster)
                <aside class="ac-dean-card" aria-label="{{ $college->name }}">
                    @if ($college->programs->isNotEmpty() && $label('dean_card_programs'))
                        <p class="ac-pill">★ {{ $label('dean_card_programs') }}</p>
                    @endif
                    @if ($college->dean_name)
                        <div class="ac-dean">
                            @if ($college->dean_photo)
                                <img class="ac-avatar ac-avatar-lg" src="{{ \App\Support\Thumb::url($college->dean_photo) }}" alt="" width="64" height="64">
                            @else
                                <span class="ac-avatar ac-avatar-lg" aria-hidden="true">{{ \App\Models\FacultyMember::initials($college->dean_name) }}</span>
                            @endif
                            <div>
                                @if ($college->dean_title)<span class="ac-kicker">{{ Str::before($college->dean_title, ',') }}</span>@endif
                                <strong>{{ $college->dean_name }}</strong>
                                @if (Str::contains((string) $college->dean_title, ','))<span class="ac-dean-title">{{ trim(Str::after($college->dean_title, ',')) }}</span>@endif
                            </div>
                        </div>
                    @endif
                    @if ($college->research_cluster)
                        <div class="ac-dean-cluster">
                            <span class="ac-kicker">{{ $label('research_cluster') }}</span>
                            <strong>{{ $college->research_cluster }}</strong>
                            @if ($college->research_agenda_url)
                                <a href="{{ $college->research_agenda_url }}" rel="noopener">{{ $label('see_agenda') }} <span aria-hidden="true">→</span></a>
                            @elseif (isset($shown['research']))
                                <a href="#research">{{ $label('see_research') }} <span aria-hidden="true">→</span></a>
                            @endif
                        </div>
                    @endif
                </aside>
            @endif
        </div>
    </section>

    @if ($stats)
        <section class="ac-stats" aria-label="{{ $short }}">
            <div class="ac-container ac-stats-grid">
                @foreach ($stats as $statLabel => $value)
                    <div class="ac-stat"><strong>{{ $value }}</strong><span>{{ Site::fill($statLabel, $replace) }}</span></div>
                @endforeach
            </div>
        </section>
    @endif

    @if (count($shown) > 1)
        <nav class="ac-subnav" aria-label="{{ $short }}">
            <div class="ac-container">
                @foreach ($shown as $type => $section)
                    @if (! empty($section['nav_label']))<a href="#{{ $type }}">{{ $heading($section, 'nav_label') }}</a>@endif
                @endforeach
            </div>
        </nav>
    @endif

    @foreach ($shown as $type => $section)
        @switch($type)
            @case('about')
                <section class="ac-section" id="about" aria-labelledby="about-heading">
                    <div class="ac-container">
                        @include('academics.partials.section-head', ['id' => 'about-heading'])

                        @if ($college->overview)<div class="ac-prose ac-prose-lead ac-about-intro">{!! Prose::html($college->overview) !!}</div>@endif

                        @if ($college->vision || $college->mission || $college->video_url)
                            <div class="ac-about-row">
                                @if ($college->vision)<div class="ac-vm"><p class="ac-kicker">{{ $label('vision') }}</p><p>{{ $college->vision }}</p></div>@endif
                                @if ($college->mission)<div class="ac-vm"><p class="ac-kicker">{{ $label('mission') }}</p><p>{{ $college->mission }}</p></div>@endif
                                @if ($college->video_url)
                                    <figure class="ac-video">
                                        <a href="{{ $college->video_url }}" target="_blank" rel="noopener">
                                            @if ($college->youtubeId())
                                                <img src="https://i.ytimg.com/vi/{{ $college->youtubeId() }}/hqdefault.jpg" alt="" width="480" height="360" loading="lazy" decoding="async">
                                            @elseif ($college->banner_image)
                                                <img src="{{ \App\Support\Thumb::url($college->banner_image) }}" alt="" width="480" height="270" loading="lazy" decoding="async">
                                            @endif
                                            <span class="ac-video-play" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span>
                                            <span class="sr-only">{{ $label('video_link') }}</span>
                                        </a>
                                        <figcaption>{{ $college->video_caption ?: $label('video_caption') }}</figcaption>
                                    </figure>
                                @endif
                            </div>
                        @endif

                        @foreach (['goals', 'objectives'] as $field)
                            @if ($college->$field)
                                <div class="ac-panel">
                                    <h3>{{ $label($field) }}</h3>
                                    {{-- Long lists (some colleges list every program outcome) show a preview first. --}}
                                    <div class="ac-prose ac-prose-grid" id="ac-{{ $field }}"
                                         @if (mb_strlen($college->$field) > 1500) data-clamp data-more="{{ $label('show_all') }}" data-less="{{ $label('show_less') }}" @endif>{!! Prose::html($college->$field) !!}</div>
                                </div>
                            @endif
                        @endforeach

                        @if ($college->dean_message)
                            <figure class="ac-message">
                                <blockquote>{!! nl2br(e($college->dean_message)) !!}</blockquote>
                                <figcaption><strong>{{ $college->dean_name }}</strong> · {{ $college->dean_title }}</figcaption>
                            </figure>
                        @endif
                    </div>
                </section>
                @break

            @case('programs')
                <section class="ac-section ac-section-tint" id="programs" aria-labelledby="programs-heading">
                    <div class="ac-container">
                        @include('academics.partials.section-head', ['id' => 'programs-heading', 'link' => route('academics.programs'), 'linkLabel' => $label('all_programs')])
                        <div class="ac-offer-grid">
                            @foreach ($college->programs as $program)
                                <article class="ac-offer">
                                    <p class="ac-offer-code">{{ $program->code ?: $program->degreeLabel() }}</p>
                                    <h3><a href="{{ route('academics.program', $program) }}">{{ $program->name }}</a></h3>
                                    @if ($program->majors)<p class="ac-offer-majors">{{ $label('majors') }}: {{ $program->majors }}</p>@endif
                                    @if ($program->summary)<p>{{ $program->summary }}</p>@endif
                                    <div class="ac-offer-links">
                                        <a href="{{ route('academics.program', $program) }}">{{ $label('program_details') }} <span aria-hidden="true">→</span></a>
                                        @if ($college->email && $label('ask_program'))
                                            <a href="mailto:{{ $college->email }}?subject={{ rawurlencode(Site::text('college.labels.ask_subject', ['program' => $program->name])) }}">{{ $label('ask_program') }} <span aria-hidden="true">→</span></a>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        @if ($college->departments->isNotEmpty())
                            <p class="ac-departments">
                                {{ $label('departments') }}:
                                @foreach ($college->departments as $department)
                                    <a href="{{ route('academics.department', [$college, $department]) }}">{{ $department->name }}</a>@if (! $loop->last) · @endif
                                @endforeach
                            </p>
                        @endif
                    </div>
                </section>
                @break

            @case('research')
                <section class="ac-section" id="research" aria-labelledby="research-heading">
                    <div class="ac-container">
                        <div class="ac-research-grid">
                            <div>
                                @include('academics.partials.section-head', ['id' => 'research-heading'])
                                @if ($college->research_cluster)
                                    <div class="ac-cluster">
                                        @if ($college->research_emblem)<img src="{{ asset('storage/' . $college->research_emblem) }}" alt="{{ $college->research_cluster }}" width="76" height="76" loading="lazy">@endif
                                        <div>
                                            <strong>{{ $college->research_cluster }}</strong>
                                            @if ($college->research_cluster_name)<span>{{ $college->research_cluster_name }}</span>@endif
                                        </div>
                                    </div>
                                @endif
                                @if ($college->research_description)<p class="ac-prose">{{ $college->research_description }}</p>@endif
                                @if ($college->research_coordinator)<p class="ac-small"><strong>{{ $label('research_coordinator') }}:</strong> {{ $college->research_coordinator }}</p>@endif
                                <div class="ac-hero-actions">
                                    @if ($college->research_agenda_url)<a class="ac-button ac-button-ghost" href="{{ $college->research_agenda_url }}" rel="noopener">{{ $label('research_agenda') }} <span aria-hidden="true">→</span></a>@endif
                                    @if ($college->research_repository_url)<a class="ac-button ac-button-ghost" href="{{ $college->research_repository_url }}" rel="noopener">{{ $label('research_repository') }} <span aria-hidden="true">→</span></a>@endif
                                </div>
                            </div>

                            @if ($college->research_highlights)
                                <div>
                                    <h3 class="ac-subkicker ac-subkicker-top">{{ $label('research_highlights') }}</h3>
                                    <ul class="ac-highlights">
                                        @foreach ($college->research_highlights as $item)
                                            <li>
                                                @if (! empty($item['url']))<a href="{{ $item['url'] }}" rel="noopener">@endif
                                                <strong>{{ $item['title'] ?? '' }}</strong>
                                                @if (! empty($item['description']))<span>{{ $item['description'] }}</span>@endif
                                                @if (! empty($item['url']))</a>@endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        @if ($college->focus_areas)
                            <h3 class="ac-subkicker">{{ $label('focus_areas') }}</h3>
                            <div class="ac-focus-grid">
                                @foreach ($college->focus_areas as $area)
                                    <div class="ac-focus">
                                        <h4>{{ $area['area'] ?? '' }}</h4>
                                        @if (! empty($area['topics']))
                                            <ul>
                                                @foreach (preg_split('/\R/', $area['topics'], -1, PREG_SPLIT_NO_EMPTY) as $topic)
                                                    <li>{{ ltrim($topic, '-• ') }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        @include('academics.partials.sdgs', ['sdgs' => $area['sdgs'] ?? []])
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
                @break

            @case('partners')
                <section class="ac-section ac-section-tint" id="partners" aria-labelledby="partners-heading">
                    <div class="ac-container">
                        @include('academics.partials.section-head', ['id' => 'partners-heading'])
                        @if ($college->partners_intro)<p class="ac-section-intro">{{ $college->partners_intro }}</p>@endif

                        @if ($partners->isNotEmpty())
                            <div class="ac-partner-grid">
                                @foreach ($partners as $partner)
                                    @include('academics.partials.partner-card')
                                @endforeach
                            </div>
                        @endif

                        @if ($college->linkages)
                            <h3 class="ac-linkages-title">{{ $label('linkages') }}</h3>
                            <div class="ac-linkage-grid">
                                @foreach ($college->linkages as $linkage)
                                    @php($tag = empty($linkage['url']) ? 'div' : 'a')
                                    <{{ $tag }} class="ac-linkage" @if ($tag === 'a') href="{{ $linkage['url'] }}" rel="noopener" @endif>
                                        <strong>{{ $linkage['name'] ?? '' }}</strong>
                                        @if (! empty($linkage['description']))<span>{{ $linkage['description'] }}</span>@endif
                                    </{{ $tag }}>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
                @break

            @case('students')
                <section class="ac-section" id="students" aria-labelledby="students-heading">
                    <div class="ac-container">
                        @include('academics.partials.section-head', ['id' => 'students-heading'])
                        <div class="ac-benefit-grid">
                            @foreach ($college->student_benefits as $i => $benefit)
                                <article class="ac-benefit">
                                    <span class="ac-benefit-icon" aria-hidden="true">
                                        @switch($i % 4)
                                            @case(0)<svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M16 4.5a3.5 3.5 0 0 1 0 7M18 14c1.9.7 3 2.6 3 6"/></svg>@break
                                            @case(1)<svg viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>@break
                                            @case(2)<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M8.5 14.5s1.3 2 3.5 2 3.5-2 3.5-2M9 9.5h.01M15 9.5h.01"/></svg>@break
                                            @default<svg viewBox="0 0 24 24"><path d="M5 4h14v16l-7-4-7 4z"/></svg>
                                        @endswitch
                                    </span>
                                    <h3>{{ $benefit['title'] ?? '' }}</h3>
                                    @if (! empty($benefit['description']))<p>{{ $benefit['description'] }}</p>@endif
                                    @if (! empty($benefit['url']))<a class="ac-link" href="{{ $benefit['url'] }}" rel="noopener">{{ $label('read_more') }} <span aria-hidden="true">→</span></a>@endif
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>
                @break

            @case('accreditation')
                <section class="ac-section" id="accreditation" aria-labelledby="accreditation-heading">
                    <div class="ac-container">
                        @include('academics.partials.section-head', ['id' => 'accreditation-heading'])
                        @if ($accredited->isNotEmpty())
                            <h3 class="ac-linkages-title">{{ $label('accredited_programs') }}</h3>
                            <ul class="ac-accredited-list">
                                @foreach ($accredited as $program)
                                    <li>
                                        <a href="{{ route('academics.program', $program) }}">{{ $program->name }}</a>
                                        @foreach ($program->accreditations as $accreditation)
                                            <span class="ac-accredited-badge">{{ $accreditation->label() }}@if ($accreditation->valid_until && $label('valid_until')) · {{ $label('valid_until') }} {{ $accreditation->valid_until->format('F Y') }}@endif</span>
                                        @endforeach
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if ($recognitions)
                            <h3 class="ac-linkages-title">{{ $label('university_recognitions') }}</h3>
                            @include('academics.partials.recognitions', ['items' => $recognitions, 'level' => 4])
                        @endif
                    </div>
                </section>
                @break

            @case('extension')
                <section class="ac-section ac-section-tint" id="extension" aria-labelledby="extension-heading">
                    <div class="ac-container">
                        @include('academics.partials.section-head', ['id' => 'extension-heading'])
                        <ul class="ac-projects ac-projects-grid">
                            @foreach ($college->extension_projects as $project)
                                <li>
                                    <h3>{{ $project['title'] ?? '' }}</h3>
                                    <p>{{ $project['description'] ?? '' }}@if (! empty($project['year'])) ({{ $project['year'] }})@endif</p>
                                    @if (! empty($project['url']))
                                        <div class="ac-project-foot"><a href="{{ $project['url'] }}" rel="noopener">{{ $label('read_more') }} <span aria-hidden="true">→</span></a></div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </section>
                @break

            @case('faculty')
                <section class="ac-section" id="faculty" aria-labelledby="faculty-heading">
                    <div class="ac-container">
                        @include('academics.partials.section-head', ['id' => 'faculty-heading'])
                        {{-- An organization chart by default (reviewers, November 2026), built from positions and departments --}}
                        @include('academics.partials.faculty-tree')
                    </div>
                </section>
                @break

            @case('resources')
                <section class="ac-section ac-section-tint" id="resources" aria-labelledby="resources-heading">
                    <div class="ac-container ac-two-col">
                        @if ($college->facilities)
                            <div>
                                @include('academics.partials.section-head', ['id' => 'resources-heading'])
                                <div class="ac-prose">{!! Prose::html($college->facilities) !!}</div>
                            </div>
                        @endif
                        @if ($college->documents->isNotEmpty())
                            <div>
                                @include('academics.partials.section-head', ['id' => $college->facilities ? 'documents-heading' : 'resources-heading', 'section' => ['kicker' => $label('documents_kicker'), 'heading' => $label('documents_heading')]])
                                @include('academics.partials.documents', ['documents' => $college->documents])
                            </div>
                        @endif
                    </div>
                </section>
                @break

            @case('news')
                <section class="ac-section" id="news" aria-labelledby="news-heading">
                    <div class="ac-container">
                        @include('academics.partials.section-head', ['id' => 'news-heading', 'link' => route('news.index', ['college' => $college->slug]), 'linkLabel' => $label('all_news')])
                        <div class="ac-updates {{ $news->isNotEmpty() && $events->isNotEmpty() ? 'has-events' : '' }}">
                            @if ($news->isNotEmpty())
                                <div class="ac-news-grid">
                                    @foreach ($news as $story)
                                        @include('partials.news-card')
                                    @endforeach
                                </div>
                            @endif
                            @if ($events->isNotEmpty())
                                <aside aria-label="{{ $label('events_heading') }}">
                                    <h3 class="ac-subhead">{{ $label('events_heading') }}</h3>
                                    @include('academics.partials.events')
                                </aside>
                            @endif
                        </div>
                    </div>
                </section>
                @break
        @endswitch
    @endforeach

    @include('academics.partials.enroll', ['heading' => $label('cta_heading')])
</main>

@endsection
