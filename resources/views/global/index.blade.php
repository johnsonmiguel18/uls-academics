@extends('layouts.app', ['area' => 'global'])

@use('App\Support\Site')
@use('App\Models\Term')

{{-- Global Engagement & Linkages. Texts, sections and order: Site settings → Global engagement.
     Partners: each college's partner list plus the university-wide list there. --}}
@php
    $label = fn (string $key, array $replace = []) => Site::text("global.labels.$key", $replace);
    $scopes = Term::options('partner_scope');
    $storyCategories = Site::get('global.story_categories', []);
@endphp

@section('title', Site::text('global.meta_title'))

@include('academics.partials.seo', [
    'title' => Site::text('global.meta_title'),
    'description' => Site::text('global.meta_description'),
])

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/academics-college.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/global.css') }}">
    @if ($stories->isNotEmpty())<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/news-card.css') }}">@endif
@endpush

@section('content')
<main class="ac-page">
    <section class="ac-hero gl-hero">
        <div class="ac-container">
            @if (Site::text('global.hero.kicker'))<p class="ac-kicker">{{ Site::text('global.hero.kicker') }}</p>@endif
            <h1>{{ Site::text('global.hero.heading') }} @if (Site::text('global.hero.heading_accent'))<em>{{ Site::text('global.hero.heading_accent') }}</em>@endif</h1>
            @if (Site::text('global.hero.intro'))<p class="ac-lead">{{ Site::text('global.hero.intro') }}</p>@endif
            <div class="ac-hero-actions">
                @if (isset($sections['partners']) && Site::text('global.hero.primary_label'))
                    <a class="ac-button ac-button-light" href="#partners">{{ Site::text('global.hero.primary_label') }}</a>
                @endif
                @if (isset($sections['connect']) && Site::text('global.hero.secondary_label'))
                    <a class="ac-button ac-button-outline-light" href="#connect">{{ Site::text('global.hero.secondary_label') }}</a>
                @endif
            </div>
        </div>
    </section>

    @foreach ($sections as $type => $section)
        @switch($type)
            @case('pathways')
                <section class="ac-section gl-pathways" aria-labelledby="pathways-heading">
                    <div class="ac-container">
                        @include('academics.partials.section-head', ['id' => 'pathways-heading'])
                        <ul class="gl-cards">
                            @foreach (Site::get('global.pathways', []) as $i => $card)
                                <li>
                                    <span class="gl-card-number" aria-hidden="true">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                    <h3>{{ $card['title'] ?? '' }}</h3>
                                    <p>{{ $card['text'] ?? '' }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </section>
                @break

            @case('about')
                <section class="ac-section ac-section-tint" id="about" aria-labelledby="about-heading">
                    <div class="ac-container gl-about">
                        @include('academics.partials.section-head', ['id' => 'about-heading'])
                        <div class="gl-prose">
                            @foreach (preg_split('/\n\s*\n/', trim(Site::text('global.about'))) as $paragraph)
                                @if ($paragraph !== '')<p>{{ $paragraph }}</p>@endif
                            @endforeach
                        </div>
                    </div>
                </section>
                @break

            @case('partners')
                <section class="ac-section" id="partners" aria-labelledby="partners-heading">
                    <div class="ac-container">
                        @include('academics.partials.section-head', ['id' => 'partners-heading'])
                        @if (Site::text('global.partners_intro'))<p class="ac-section-intro">{{ Site::text('global.partners_intro') }}</p>@endif

                        <div class="ac-stats-grid gl-stats">
                            <div class="ac-stat"><strong>{{ $stats['partners'] }}</strong><span>{{ $label('stat_partners') }}</span></div>
                            <div class="ac-stat"><strong>{{ $stats['local'] }}</strong><span>{{ $label('stat_local') }}</span></div>
                            <div class="ac-stat"><strong>{{ $stats['international'] }}</strong><span>{{ $label('stat_international') }}</span></div>
                            <div class="ac-stat"><strong>{{ $stats['colleges'] }}</strong><span>{{ $label('stat_colleges') }}</span></div>
                        </div>

                        @if ($partners->isEmpty())
                            <p class="ac-muted">{{ $label('partners_empty') }}</p>
                        @else
                            <div class="gl-filters" data-partner-filters hidden>
                                <label>
                                    <span>{{ $label('filter_college') }}</span>
                                    <select data-filter="college">
                                        <option value="">{{ $label('all_colleges') }}</option>
                                        @foreach ($colleges as $college)
                                            <option value="{{ $college->slug }}">{{ $college->name }}</option>
                                        @endforeach
                                        @if ($partners->contains(fn ($p) => ! $p['college']))
                                            <option value="university">{{ $label('university_wide') }}</option>
                                        @endif
                                    </select>
                                </label>
                                <label>
                                    <span>{{ $label('filter_scope') }}</span>
                                    <select data-filter="scope">
                                        <option value="">{{ $label('all_scopes') }}</option>
                                        @foreach ($scopes as $key => $scopeLabel)
                                            <option value="{{ $key }}">{{ $scopeLabel }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <p class="gl-showing" aria-live="polite" data-showing="{{ $label('showing') }}">{{ $label('showing', ['shown' => $partners->count(), 'count' => $partners->count()]) }}</p>
                            </div>

                            <ul class="ac-partner-grid gl-partners">
                                @foreach ($partners as $partner)
                                    <li data-college="{{ $partner['college']?->slug ?? 'university' }}" data-scope="{{ $partner['scope'] }}">
                                        @include('academics.partials.partner-card', ['showCollege' => true])
                                    </li>
                                @endforeach
                            </ul>
                            <p class="ac-muted" data-no-match hidden>{{ $label('no_match') }}</p>
                        @endif
                    </div>
                </section>
                @break

            @case('opportunities')
                <section class="ac-section ac-section-tint" id="opportunities" aria-labelledby="opportunities-heading">
                    <div class="ac-container">
                        @include('academics.partials.section-head', ['id' => 'opportunities-heading'])
                        <ul class="gl-cards gl-cards-4">
                            @foreach (Site::get('global.opportunities', []) as $card)
                                <li><h3>{{ $card['title'] ?? '' }}</h3><p>{{ $card['text'] ?? '' }}</p></li>
                            @endforeach
                        </ul>
                        @if (Site::get('global.exchange_items'))
                            <div class="gl-exchange">
                                <h3>{{ Site::text('global.exchange_heading') }}</h3>
                                <ul>
                                    @foreach (Site::get('global.exchange_items', []) as $item)
                                        <li>{{ $item['text'] ?? '' }}</li>
                                    @endforeach
                                </ul>
                                @if (Site::text('global.exchange_note'))<p class="ac-small ac-muted">{{ Site::text('global.exchange_note') }}</p>@endif
                            </div>
                        @endif
                    </div>
                </section>
                @break

            @case('sdg')
                @php($goal17 = \App\Support\Sdg::goals()[17] ?? null)
                <section class="ac-section gl-sdg" aria-labelledby="sdg-heading" @if ($goal17) style="--sdg: {{ $goal17[1] }}; --sdg-ink: {{ $goal17[2] }}" @endif>
                    <div class="ac-container gl-sdg-inner">
                        <span class="gl-sdg-badge" aria-hidden="true">17</span>
                        <div>
                            @include('academics.partials.section-head', ['id' => 'sdg-heading'])
                            <p>{{ Site::text('global.sdg_text') }}</p>
                            @if ($label('sdg_link'))
                                <a class="ac-button ac-button-ghost" href="{{ route('sdgs.show', 17) }}">{{ $label('sdg_link') }} <span aria-hidden="true">→</span></a>
                            @endif
                        </div>
                    </div>
                </section>
                @break

            @case('stories')
                @if ($stories->isNotEmpty())
                    <section class="ac-section ac-section-tint" id="stories" aria-labelledby="stories-heading">
                        <div class="ac-container">
                            @include('academics.partials.section-head', [
                                'id' => 'stories-heading',
                                'link' => route('news.index', count($storyCategories) === 1 ? ['category' => $storyCategories[0]] : []),
                            ])
                            <div class="ac-news-grid">
                                @foreach ($stories as $story)
                                    @include('partials.news-card')
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif
                @break

            @case('faq')
                @if (Site::get('global.faq'))
                    <section class="ac-section" aria-labelledby="faq-heading">
                        <div class="ac-container gl-faq">
                            @include('academics.partials.section-head', ['id' => 'faq-heading'])
                            <div>
                                @foreach (Site::get('global.faq', []) as $item)
                                    <details>
                                        <summary>{{ $item['question'] ?? '' }}</summary>
                                        <p>{{ $item['answer'] ?? '' }}</p>
                                    </details>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif
                @break

            @case('connect')
                <section class="ac-section gl-connect" id="connect" aria-labelledby="connect-heading">
                    <div class="ac-container gl-connect-grid">
                        <div>
                            @include('academics.partials.section-head', ['id' => 'connect-heading'])
                            @if (Site::text('global.connect_text'))<p>{{ Site::text('global.connect_text') }}</p>@endif
                            @if (Site::text('contact.address'))
                                <h3>{{ $label('address_heading') }}</h3>
                                <p>{{ Site::text('contact.address') }}@if (Site::text('contact.phone'))<br>{{ Site::text('contact.phone') }}@endif</p>
                            @endif
                        </div>

                        <form class="gl-form" method="post" action="{{ route('global.inquire') }}" novalidate>
                            @if (session('inquiry_sent'))
                                <p class="gl-form-status is-success" role="status">{{ $label('form_sent') }}</p>
                            @elseif ($errors->any())
                                <p class="gl-form-status is-error" role="alert">{{ $label('form_errors') }}</p>
                            @endif

                            @foreach (['name' => 'text', 'email' => 'email', 'organization' => 'text'] as $field => $type)
                                <label for="gl-{{ $field }}">{{ $label("form_$field") }}</label>
                                <input id="gl-{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field) }}"
                                       @if ($field !== 'organization') required @endif
                                       @if ($field === 'email') autocomplete="email" @elseif ($field === 'name') autocomplete="name" @else autocomplete="organization" @endif
                                       @error($field) aria-invalid="true" aria-describedby="gl-{{ $field }}-error" @enderror>
                                @error($field)<p class="gl-error" id="gl-{{ $field }}-error">{{ $message }}</p>@enderror
                            @endforeach

                            @if (Site::get('global.interests'))
                                <label for="gl-interest">{{ $label('form_interest') }}</label>
                                <select id="gl-interest" name="interest">
                                    <option value="">{{ $label('form_choose') }}</option>
                                    @foreach (Site::get('global.interests', []) as $key => $interest)
                                        <option value="{{ $key }}" @selected(old('interest') === $key)>{{ $interest }}</option>
                                    @endforeach
                                </select>
                            @endif

                            <label for="gl-message">{{ $label('form_message') }}</label>
                            <textarea id="gl-message" name="message" rows="5" required @error('message') aria-invalid="true" aria-describedby="gl-message-error" @enderror>{{ old('message') }}</textarea>
                            @error('message')<p class="gl-error" id="gl-message-error">{{ $message }}</p>@enderror

                            {{-- Trap for spam bots: hidden from people and screen readers. --}}
                            <div class="gl-trap" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                            <button type="submit" class="ac-button">{{ $label('form_submit') }}</button>
                            @if ($label('form_privacy'))<p class="ac-small ac-muted">{{ $label('form_privacy') }}</p>@endif
                        </form>
                    </div>
                </section>
                @break
        @endswitch
    @endforeach
</main>
@endsection

@push('scripts')
<script>
    // Partner filters (all partners are listed without JavaScript).
    (() => {
        const box = document.querySelector('[data-partner-filters]');
        if (!box) return;
        box.hidden = false;
        const items = [...document.querySelectorAll('.gl-partners > li')];
        const showing = box.querySelector('[data-showing]');
        const none = document.querySelector('[data-no-match]');
        const apply = () => {
            const want = Object.fromEntries([...box.querySelectorAll('select')].map(s => [s.dataset.filter, s.value]));
            let shown = 0;
            items.forEach(li => {
                const ok = (!want.college || li.dataset.college === want.college) && (!want.scope || li.dataset.scope === want.scope);
                li.hidden = !ok; shown += ok;
            });
            showing.textContent = showing.dataset.showing.replace(':shown', shown).replace(':count', items.length);
            none.hidden = shown > 0;
        };
        box.addEventListener('change', apply);
    })();
</script>
@endpush
