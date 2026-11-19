<x-filament-widgets::widget>
    <div class="uls-welcome">
        <div style="position: relative; z-index: 1">
            <p class="uls-welcome-eyebrow">{{ now()->format('l, F j, Y') }}@if ($role) · {{ \Illuminate\Support\Str::headline($role) }}@endif</p>
            <h2>{{ $greeting }}, {{ $name }}!</h2>
            <p>Keep ULS Academics current: update your college profile, programs and faculty, and post college news.</p>

            <div class="uls-welcome-actions">
                @if ($newNewsUrl)
                    <a href="{{ $newNewsUrl }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        Post news
                    </a>
                @endif
                @if ($newProgramUrl)
                    <a href="{{ $newProgramUrl }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg>
                        New program
                    </a>
                @endif
                <a href="{{ url('/') }}" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>
                    View Academics site
                </a>
            </div>
        </div>

        <img class="uls-welcome-seal" src="{{ \App\Support\Site::logo() }}" alt="">
    </div>
</x-filament-widgets::widget>
