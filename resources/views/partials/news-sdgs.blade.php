{{--
    A story's SDGs as small labels in each goal's color (Admin → Lists and categories → SDGs),
    each linking to the news filtered by that goal. Shows at most $max, then "+N".
    @include('partials.news-sdgs', ['sdgs' => $story->sdgs, 'max' => 3])
--}}
@php
    $goals = \App\Support\Sdg::goals();
    $numbers = collect($sdgs ?? [])->map(fn ($n) => (int) $n)->unique()->sort()->filter(fn ($n) => isset($goals[$n]))->values();
    $max ??= 3;
    $shown = $numbers->take($max);
    $rest = $numbers->slice($max);
@endphp
@if ($numbers->isNotEmpty())
    <ul class="news-sdgs" aria-label="{{ \App\Support\Site::text('a11y.sdgs') }}">
        @foreach ($shown as $n)
            <li>
                <a class="news-sdg sdg-{{ $n }}" href="{{ route('news.index', ['sdg' => $n]) }}">{{ \App\Support\Site::text('sdgs.labels.goal_kicker', ['number' => $n]) }}<span class="sr-only">: {{ $goals[$n][0] }}</span></a>
            </li>
        @endforeach
        @if ($rest->isNotEmpty())
            <li class="news-sdg-more">
                <span aria-hidden="true">{{ \App\Support\Site::text('news.labels.more_sdgs', ['count' => $rest->count()]) }}</span>
                <span class="sr-only">{{ $rest->map(fn ($n) => \App\Support\Site::text('sdgs.labels.goal_kicker', ['number' => $n]) . ': ' . $goals[$n][0])->join(', ') }}</span>
            </li>
        @endif
    </ul>
@endif
