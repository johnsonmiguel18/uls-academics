@extends('layouts.app')

@use('App\Support\Site')

@section('title', Site::text('not_found.heading') . ' | ' . Site::text('contact.site_name'))

{{-- Text: Site settings → Finder, news, 404 → Page not found --}}
@section('content')
<main class="ac-page">
    <section class="ac-hero ac-hero-compact">
        <div class="ac-container">
            @if (Site::text('not_found.kicker'))<p class="ac-kicker">{{ Site::text('not_found.kicker') }}</p>@endif
            <h1>{{ Site::text('not_found.heading') }}</h1>
        </div>
    </section>
    <section class="ac-section">
        <div class="ac-container">
            @if (Site::text('not_found.text'))<p class="ac-prose">{{ Site::text('not_found.text') }}</p>@endif
            <div class="ac-hero-actions">
                @if (Site::text('not_found.primary_label'))<a class="ac-button" href="{{ route('academics.programs') }}">{{ Site::text('not_found.primary_label') }}</a>@endif
                @if (Site::text('not_found.secondary_label'))<a class="ac-button ac-button-ghost" href="{{ route('academics.index') }}">{{ Site::text('not_found.secondary_label') }}</a>@endif
            </div>
        </div>
    </section>
</main>
@endsection
