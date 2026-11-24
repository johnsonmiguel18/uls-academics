{{-- The news front page: lead story with three headlines under it, story cards (a swipe rail on phones) and
     "More headlines" beside them. Used by the news page and the home page so both look the same.
     $featuredNews, $related, $cards, $latestNews; $prefix for ids; $level = heading level of its sections;
     optional $paginator and $filtered (news page). --}}
@php
    $label = fn (string $key, array $replace = []) => \App\Support\Site::text("news.labels.$key", $replace);
    $prefix ??= 'news';
    $level ??= 2;
@endphp
<div class="news-front {{ $latestNews->isNotEmpty() ? 'has-aside' : '' }}">
    <div class="news-front-main">
        @if ($featuredNews)
            <section class="news-front-section" aria-labelledby="{{ $prefix }}-featured-heading">
                <h{{ $level }} class="sr-only" id="{{ $prefix }}-featured-heading">{{ $label('featured_heading') }}</h{{ $level }}>

                <article class="news-feature news-lead-story">
                    <div class="news-lead-head">
                        <div class="news-meta">
                            <span class="news-category">{{ \App\Models\Term::label('news_category', $featuredNews->category) }}</span>
                            <time datetime="{{ ($featuredNews->published_at ?? $featuredNews->created_at)?->toDateString() }}">{{ $featuredNews->displayDate() }}</time>
                        </div>
                        <h{{ $level + 1 }}><a href="{{ route('news.show', $featuredNews->slug) }}">{{ $featuredNews->title }}</a></h{{ $level + 1 }}>
                    </div>

                    <a href="{{ route('news.show', $featuredNews->slug) }}" class="news-feature-media" tabindex="-1" aria-hidden="true">
                        @if ($featuredNews->featured_image)
                            <img src="{{ \App\Support\Thumb::url($featuredNews->featured_image, 960) }}" srcset="{{ \App\Support\Thumb::srcset($featuredNews->featured_image) }}" sizes="(max-width: 1099px) 92vw, 540px" alt="" fetchpriority="high" decoding="async">
                        @else
                            <span class="news-media-fallback"><img src="{{ \App\Support\Site::logo() }}" alt=""></span>
                        @endif
                    </a>

                    <div class="news-lead-body">
                        <p class="news-summary">{{ $featuredNews->summary(220) }}</p>

                        @include('partials.news-sdgs', ['sdgs' => $featuredNews->sdgs, 'max' => 3])
                    </div>

                    {{-- The next three stories as plain headlines, in a row under the lead (CNN-style) --}}
                    @if ($related->isNotEmpty())
                        <ul class="news-lead-related" aria-label="{{ $label('related_heading') }}">
                            @foreach ($related as $story)
                                <li><a href="{{ route('news.show', $story->slug) }}">{{ $story->title }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            </section>
        @endif

        <section class="news-front-section" aria-labelledby="{{ $prefix }}-list-heading">
            <h{{ $level }} class="news-index-heading" id="{{ $prefix }}-list-heading">
                {{ ($filtered ?? false) ? $label('results_heading') : $label('list_heading') }}
            </h{{ $level }}>

            @if ($cards->isNotEmpty())
                {{-- A grid on wider screens; on phones a sideways swipe rail (keyboard: focus it, then arrow keys).
                     On phones the site script shows arrow buttons and dots while there is more to swipe. --}}
                <div class="news-rail-wrap">
                    <div class="news-cards news-rail" id="{{ $prefix }}-rail" tabindex="0" role="group" aria-labelledby="{{ $prefix }}-list-heading">
                        @foreach ($cards as $story)
                            @include('partials.news-card', ['story' => $story])
                        @endforeach
                    </div>
                    <button type="button" class="news-rail-arrow is-prev" data-rail="prev" aria-controls="{{ $prefix }}-rail" aria-label="{{ $label('rail_previous') }}" hidden>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button type="button" class="news-rail-arrow is-next" data-rail="next" aria-controls="{{ $prefix }}-rail" aria-label="{{ $label('rail_next') }}" hidden>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                    <div class="news-rail-dots" aria-hidden="true" hidden></div>
                </div>

                @isset($paginator){{ $paginator->onEachSide(1)->links('pagination::default') }}@endisset
            @else
                <div class="news-empty-state">
                    <h{{ $level + 1 }}>{{ $label('empty_heading') }}</h{{ $level + 1 }}>
                    <p>{{ $label('empty_text') }}</p>
                    @if ($filtered ?? false)
                        <a href="{{ route('news.index') }}" class="home-btn home-btn-outline">{{ $label('view_all') }}</a>
                    @endif
                </div>
            @endif
        </section>
    </div>

    @if ($latestNews->isNotEmpty())
        <aside class="news-front-aside" aria-labelledby="{{ $prefix }}-latest-heading">
            <h{{ $level }} class="news-index-heading" id="{{ $prefix }}-latest-heading">{{ $label('latest_heading') }}</h{{ $level }}>
            <div class="news-rows">
                @foreach ($latestNews as $story)
                    @include('partials.news-card', ['story' => $story, 'variant' => 'row'])
                @endforeach
            </div>
        </aside>
    @endif
</div>
