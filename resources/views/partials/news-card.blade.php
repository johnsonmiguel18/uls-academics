{{-- One story card: image, category and date, title, short description --}}
<article class="news-card">
    <a href="{{ route('news.show', $story->slug) }}" class="news-card-media" tabindex="-1" aria-hidden="true">
        @if ($story->featured_image)
            <img src="{{ \App\Support\Thumb::url($story->featured_image) }}" srcset="{{ \App\Support\Thumb::srcset($story->featured_image) }}" sizes="(max-width: 600px) 92vw, 380px" alt="" loading="lazy" decoding="async">
        @else
            <span class="news-media-fallback"><img src="{{ \App\Support\Site::logo() }}" alt=""></span>
        @endif
    </a>

    <div class="news-card-body">
        <div class="news-meta">
            <span class="news-category">{{ \App\Models\Term::label('news_category', $story->category) }}</span>
            <time>{{ $story->displayDate() }}</time>
        </div>

        <h3><a href="{{ route('news.show', $story->slug) }}">{{ $story->title }}</a></h3>
        <p>{{ $story->summary(110) }}</p>
    </div>
</article>
