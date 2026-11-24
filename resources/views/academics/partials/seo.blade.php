{{-- Meta description, Open Graph and optional JSON-LD. Site name from Site settings.
     Optional: $ogTitle, $ogDescription (default: $title, $description), $keywords, $tags, $ogType, $published. --}}
@push('head')
    <meta name="description" content="{{ $description }}">
    @if (! empty($keywords))<meta name="keywords" content="{{ $keywords }}">@endif
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:site_name" content="{{ \App\Support\Site::text('contact.site_name') }}">
    <meta property="og:title" content="{{ $ogTitle ?? $title }}">
    <meta property="og:description" content="{{ $ogDescription ?? $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if (! empty($image))
        <meta property="og:image" content="{{ asset('storage/' . $image) }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif
    @if (! empty($published))<meta property="article:published_time" content="{{ $published }}">@endif
    @foreach ($tags ?? [] as $tag)
        <meta property="article:tag" content="{{ $tag }}">
    @endforeach
    @if (! empty($schema))
        <script type="application/ld+json" nonce="{{ Vite::cspNonce() }}">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endif
@endpush
