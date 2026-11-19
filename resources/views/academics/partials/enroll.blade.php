{{-- Enrollment call to action (Site settings → Finder, news, 404 → Enrollment call to action). --}}
@use('App\Support\Site')
@if (Site::text('cta.heading'))
    <section class="ac-cta" aria-labelledby="cta-heading">
        <div class="ac-container ac-cta-inner">
            <div>
                @if (Site::text('cta.kicker'))<p class="ac-kicker">{{ Site::text('cta.kicker') }}</p>@endif
                <h2 id="cta-heading">{{ $heading ?? null ?: Site::text('cta.heading') }}</h2>
                @if (Site::text('cta.text'))<p>{{ Site::text('cta.text') }}</p>@endif
            </div>
            <div class="ac-cta-actions">
                @if (Site::text('cta.primary_label') && Site::text('cta.primary_url'))
                    <a class="ac-button ac-button-light" href="{{ url(Site::text('cta.primary_url')) }}" rel="noopener">{{ Site::text('cta.primary_label') }} <span aria-hidden="true">↗</span></a>
                @endif
                @if (Site::text('cta.secondary_label') && Site::text('cta.secondary_url'))
                    <a class="ac-button ac-button-outline-light" href="{{ url(Site::text('cta.secondary_url')) }}">{{ Site::text('cta.secondary_label') }}</a>
                @endif
            </div>
        </div>
    </section>
@endif
