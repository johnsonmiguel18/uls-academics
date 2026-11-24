{{-- One partner card: college pages and the Global Engagement site. Expects $partner; $showCollege adds the college (Global site). --}}
<article @class(['ac-partner', 'has-logo' => ! empty($partner['logo'])])>
    {{-- The logo is decorative: the partner's name is the heading right after it --}}
    @if (! empty($partner['logo']))<img class="ac-partner-logo" src="{{ asset('storage/' . ltrim($partner['logo'], '/')) }}" alt="" width="64" height="64" loading="lazy" decoding="async">@endif
    @if (! empty($partner['period']))<p class="ac-kicker">{{ $partner['period'] }}</p>@endif
    <h3>{{ $partner['name'] ?? '' }}</h3>
    @php($meta = array_filter([$partner['type'] ?? null, $partner['country'] ?? null]))
    @if ($meta)<p class="ac-partner-type">{{ implode(' · ', $meta) }}</p>@endif
    @if (! empty($showCollege))
        <p class="ac-partner-college">
            @if ($partner['college'])<a href="{{ route('academics.college', $partner['college']) }}#partners">{{ $partner['college']->name }}</a>@else{{ \App\Support\Site::text('global.labels.university_wide') }}@endif
        </p>
    @endif
    @if (! empty($partner['description']))<p>{{ $partner['description'] }}</p>@endif
    @include('academics.partials.sdgs', ['sdgs' => $partner['sdgs'] ?? []])
    @if (! empty($partner['links']))
        <p class="ac-partner-links">
            @foreach ($partner['links'] as $link)
                @if (! empty($link['url']))<a href="{{ $link['url'] }}" rel="noopener">{{ $link['label'] ?? \App\Support\Site::text('college.labels.read_more') }} <span aria-hidden="true">→</span></a>@endif
            @endforeach
        </p>
    @endif
</article>
