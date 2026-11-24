{{-- The college's people as an organization chart (App\Support\FacultyTree): the dean, then one card per unit
     (Office of the Dean, each department with its chair, other faculty). Nested lists, so screen readers hear
     the levels; the connector lines are decoration. Needs $college and $label (college.labels.*). --}}
@php($tree = \App\Support\FacultyTree::for($college))
<ul class="ac-org" aria-label="{{ $label('org_label') }}">
    <li class="ac-org-top">
        @if ($tree['root'])
            <div class="ac-org-root">@include('academics.partials.org-person', ['person' => $tree['root'], 'lead' => true])</div>
        @endif
        @if ($tree['units'])
            <ul class="ac-org-units">
                @foreach ($tree['units'] as $unit)
                    <li class="ac-org-unit ac-org-unit-{{ $unit['type'] }}">
                        <h3 class="ac-org-unit-name">
                            @if ($unit['url'])
                                <a href="{{ $unit['url'] }}">{{ $unit['name'] }}</a>
                            @else
                                {{ $unit['name'] ?? $label($unit['type'] === 'office' ? 'org_office' : 'org_faculty') }}
                            @endif
                        </h3>
                        @if ($unit['lead'])
                            <div class="ac-org-lead">@include('academics.partials.org-person', ['person' => $unit['lead'], 'lead' => true])</div>
                        @endif
                        @if ($unit['members']->isNotEmpty())
                            <ul class="ac-org-members">
                                @foreach ($unit['members'] as $member)
                                    <li>@include('academics.partials.org-person', ['person' => $member, 'lead' => false])</li>
                                @endforeach
                            </ul>
                        @elseif (! $unit['lead'] && $label('org_empty'))
                            <p class="ac-muted ac-small">{{ $label('org_empty') }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </li>
</ul>
