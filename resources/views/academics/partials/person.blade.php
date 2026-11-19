{{-- Faculty member card: photo or initials, name, position, specialization. --}}
<li class="ac-person">
    @if ($person->photo)
        <img class="ac-avatar" src="{{ \App\Support\Thumb::url($person->photo) }}" alt="" loading="lazy">
    @else
        <span class="ac-avatar" aria-hidden="true">{{ \App\Models\FacultyMember::initials($person->name) }}</span>
    @endif
    <div>
        <strong>{{ $person->name }}</strong>
        @if ($person->position)<span>{{ $person->position }}</span>@endif
        @if ($person->specialization)<span class="ac-muted">{{ $person->specialization }}</span>@endif
        @if ($person->email)<a href="mailto:{{ $person->email }}">{{ $person->email }}</a>@endif
    </div>
</li>
