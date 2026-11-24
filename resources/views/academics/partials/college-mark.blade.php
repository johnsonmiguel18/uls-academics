{{-- A college's seal, or its initials in the college color when it has no seal. Decorative: the name is next to it. --}}
<span class="ac-college-mark" aria-hidden="true">
    @if ($college->seal_image)
        <img src="{{ asset('storage/' . $college->seal_image) }}" alt="" width="{{ $size ?? 96 }}" height="{{ $size ?? 96 }}" loading="lazy" decoding="async">
    @else
        <span @class(['ac-monogram', 'is-long' => mb_strlen($college->monogram()) > 3])>{{ $college->monogram() }}</span>
    @endif
</span>
