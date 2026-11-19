{{-- Upcoming events list. --}}
<ul class="ac-events">
    @foreach ($events as $event)
        <li>
            <time datetime="{{ $event->starts_at->toDateString() }}">
                <span>{{ $event->starts_at->format('M') }}</span>
                <strong>{{ $event->starts_at->format('d') }}</strong>
            </time>
            <div>
                <h3>
                    @if ($event->link)
                        <a href="{{ $event->link }}" rel="noopener">{{ $event->title }}</a>
                    @else
                        {{ $event->title }}
                    @endif
                </h3>
                <p class="ac-muted">
                    {{ $event->starts_at->format('l, g:i A') }}@if ($event->location) · {{ $event->location }}@endif
                    @if (($showCollege ?? false) && $event->college) · {{ $event->college->name }}@endif
                </p>
                @if ($event->description)<p>{{ \Illuminate\Support\Str::limit($event->description, 160) }}</p>@endif
            </div>
        </li>
    @endforeach
</ul>
