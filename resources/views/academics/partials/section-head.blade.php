{{-- Section heading from Site settings: small label, heading (with :placeholders), optional link. --}}
@php($replace = $replace ?? [])
<header class="ac-section-head">
    <div>
        @if (! empty($section['kicker']))<p class="ac-kicker">{{ \App\Support\Site::fill($section['kicker'], $replace) }}</p>@endif
        @if (! empty($section['heading']))<h2 id="{{ $id }}">{{ \App\Support\Site::fill($section['heading'], $replace) }}</h2>@endif
    </div>
    @if (! empty($link) && ! empty($section['link_label'] ?? $linkLabel ?? null))
        <a class="ac-button ac-button-ghost" href="{{ $link }}">{{ \App\Support\Site::fill($section['link_label'] ?? $linkLabel, $replace) }} <span aria-hidden="true">→</span></a>
    @endif
</header>
