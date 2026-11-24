{{-- One person in the organization chart: photo or initials, name, position, specialization. --}}
<div @class(['ac-org-person', 'is-lead' => $lead ?? false])>
    @if ($person->photo)
        <img class="ac-avatar" src="{{ \App\Support\Thumb::url($person->photo) }}" alt="" width="48" height="48" loading="lazy" decoding="async">
    @else
        <span class="ac-avatar" aria-hidden="true">{{ \App\Models\FacultyMember::initials($person->name) }}</span>
    @endif
    <div>
        <strong>{{ $person->name }}</strong>
        @if ($person->position)<span>{{ $person->position }}</span>@endif
        @if ($person->specialization)<span class="ac-muted">{{ $person->specialization }}</span>@endif
        @if ($person->email)<a href="mailto:{{ $person->email }}">{{ $person->email }}</a>@endif
    </div>
</div>
