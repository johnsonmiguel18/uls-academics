{{-- University-wide accreditation and rankings (Site settings → Home page → Accreditation and rankings).
     $items: the entries; $level: heading level of each entry (3 under a section heading). --}}
@php($tag = 'h' . ($level ?? 3))
<ul class="ac-recognition-grid">
    @foreach ($items as $item)
        <li class="ac-recognition">
            @if (! empty($item['logo']))
                <img class="ac-recognition-logo" src="{{ asset('storage/' . ltrim($item['logo'], '/')) }}" alt="" width="56" height="56" loading="lazy" decoding="async">
            @else
                <span class="ac-recognition-badge" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="9" r="6"/><path d="m8.5 14-1.5 7 5-3 5 3-1.5-7"/></svg></span>
            @endif
            <{{ $tag }}>{{ $item['name'] ?? '' }}</{{ $tag }}>
            @if (! empty($item['full_name']))<p class="ac-recognition-full">{{ $item['full_name'] }}</p>@endif
            @if (! empty($item['result']))
                <p class="ac-recognition-result"><strong>{{ $item['result'] }}</strong>@if (! empty($item['year'])) <span>· {{ $item['year'] }}</span>@endif</p>
            @endif
            @if (! empty($item['description']))<p>{{ $item['description'] }}</p>@endif
            @if (! empty($item['url']) && \App\Support\Site::text('recognitions.labels.more'))
                <a class="ac-link" href="{{ $item['url'] }}" rel="noopener">{{ \App\Support\Site::text('recognitions.labels.more', ['name' => $item['name'] ?? '']) }} <span aria-hidden="true">→</span></a>
            @endif
        </li>
    @endforeach
</ul>
