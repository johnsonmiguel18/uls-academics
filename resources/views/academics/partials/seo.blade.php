{{-- Meta description, Open Graph and optional JSON-LD. Site name from Site settings. --}}
@push('head')
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ \App\Support\Site::text('contact.site_name') }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if (! empty($image))
        <meta property="og:image" content="{{ asset('storage/' . $image) }}">
    @endif
    @if (! empty($schema))
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endif
@endpush
