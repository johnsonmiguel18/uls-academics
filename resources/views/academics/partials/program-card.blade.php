{{-- One program: photo, degree, name, summary, facts, link. Needs $program with college + accreditations loaded. --}}
@php
    $image = $program->image ?: $program->college?->banner_image;
    $accreditation = $program->accreditations->first();
@endphp
<article class="ac-program-card">
    <a href="{{ route('academics.program', $program) }}" class="ac-program-media" tabindex="-1" aria-hidden="true">
        @if ($image)
            <img src="{{ \App\Support\Thumb::url($image) }}" srcset="{{ \App\Support\Thumb::srcset($image) }}" sizes="(max-width: 700px) 92vw, 380px" width="480" height="240" alt="" loading="lazy" decoding="async">
        @endif
        <span class="ac-badge">{{ $program->degreeLabel() }}</span>
    </a>
    <div class="ac-program-body">
        @if ($showCollege ?? true)
            <p class="ac-program-college">{{ $program->college?->name }}</p>
        @endif
        <h3><a href="{{ route('academics.program', $program) }}">{{ $program->name }}</a></h3>
        @if ($program->summary)
            <p>{{ $program->summary }}</p>
        @elseif ($program->majors)
            <p>{{ \App\Support\Site::text('college.labels.majors') }}: {{ $program->majors }}</p>
        @endif
        @if ($program->duration || $accreditation)
            <ul class="ac-facts">
                @if ($program->duration)<li>{{ $program->duration }}</li>@endif
                @if ($accreditation)<li class="ac-fact-accredited">{{ $accreditation->label() }}</li>@endif
            </ul>
        @endif
        @if (\App\Support\Site::text('program.labels.card_link'))
            <span class="ac-link">{{ \App\Support\Site::text('program.labels.card_link') }} <span aria-hidden="true">→</span></span>
        @endif
    </div>
</article>
