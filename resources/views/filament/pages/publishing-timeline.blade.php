<x-filament-panels::page>
    @php
        $states = \App\Models\News::STATES + ['event' => ['Event', '#0F766E']];
        $canPublish = auth()->user()?->can('publish_news') ?? false;
    @endphp

    <div class="uls-gantt-wrap">
        {{-- Status summary, like the counters on a content calendar --}}
        <ul class="uls-gantt-counts" aria-label="Stories by status">
            @foreach (\App\Models\News::STATES as $key => [$label, $color])
                <li style="--c: {{ $color }}"><span class="uls-dot" aria-hidden="true"></span>{{ $label }} <strong>{{ $counts[$key] ?? 0 }}</strong></li>
            @endforeach
        </ul>

        <div class="uls-gantt-toolbar">
            <div class="uls-gantt-nav" role="group" aria-label="Move the timeline">
                <button type="button" wire:click="previous" aria-label="Earlier">‹</button>
                <button type="button" wire:click="today">Today</button>
                <button type="button" wire:click="next" aria-label="Later">›</button>
                <span class="uls-gantt-range">{{ $start->format('M j') }} – {{ $end->format('M j, Y') }}</span>
            </div>
            <div class="uls-gantt-nav" role="group" aria-label="Zoom">
                @foreach (\App\Filament\Pages\PublishingTimeline::ZOOMS as $key => [$label])
                    <button type="button" wire:click="$set('zoom', '{{ $key }}')" @if ($zoom === $key) aria-pressed="true" @endif>{{ $label }}</button>
                @endforeach
            </div>
            <a class="uls-gantt-new" href="{{ \App\Filament\Resources\News\NewsResource::getUrl('create') }}">+ New story</a>
        </div>

        {{-- The chart: names on the left, bars on a day grid on the right --}}
        <div class="uls-gantt" style="--day: {{ $dayWidth }}px; --w: {{ $width }}px" wire:loading.class="is-loading">
            <div class="uls-gantt-head">
                <div class="uls-gantt-side">Story</div>
                <div class="uls-gantt-days">
                    @foreach ($days as $day)
                        <div @class(['uls-gantt-day', 'is-weekend' => $day->isWeekend(), 'is-today' => $day->isToday(), 'is-month' => $day->day === 1])>
                            @if ($day->day === 1 || $loop->first)<span class="uls-gantt-month">{{ $day->format('M Y') }}</span>@endif
                            @if ($dayWidth >= 26 || $day->isMonday())<span>{{ $day->format($dayWidth >= 60 ? 'D j' : 'j') }}</span>@endif
                        </div>
                    @endforeach
                </div>
            </div>

            @forelse ($groups as $group => $items)
                <div class="uls-gantt-group" role="group" aria-label="{{ $group }}">
                    <div class="uls-gantt-row uls-gantt-grouprow">
                        <div class="uls-gantt-side"><strong>{{ $group }}</strong></div>
                        <div class="uls-gantt-track"></div>
                    </div>
                    @foreach ($items as $item)
                        <div class="uls-gantt-row">
                            <div class="uls-gantt-side">
                                <span class="uls-dot" style="--c: {{ $item['color'] }}" aria-hidden="true"></span>
                                <a href="{{ $item['url'] }}" class="uls-gantt-name">{{ $item['title'] }}</a>
                                <span class="uls-gantt-when">{{ $item['when'] }}</span>
                            </div>
                            <div class="uls-gantt-track">
                                <a href="{{ $item['url'] }}"
                                   @class(['uls-gantt-bar', 'is-' . $item['state'], 'open-start' => $item['openStart'], 'open-end' => $item['openEnd'], 'is-movable' => $item['movable']])
                                   style="--c: {{ $item['color'] }}; left: {{ $item['left'] }}px; width: {{ $item['width'] }}px"
                                   aria-label="{{ $item['title'] }}: {{ $item['label'] }}, {{ $item['when'] }}{{ $item['movable'] ? '. Alt and arrow keys move it by a day.' : '' }}"
                                   @if ($item['movable'])
                                       x-data="{ x0: 0, dx: 0, dragging: false }"
                                       x-on:pointerdown="if ($event.button === 0) { x0 = $event.clientX; dx = 0; $el.setPointerCapture($event.pointerId) }"
                                       x-on:pointermove="if (x0) { dx = $event.clientX - x0; dragging = Math.abs(dx) > 4; $el.style.translate = dragging ? dx + 'px 0' : '' }"
                                       x-on:pointerup="if (dragging) { const days = Math.round(dx / {{ $dayWidth }}); $el.style.translate = ''; if (days) $wire.reschedule({{ $item['id'] }}, days) } x0 = 0"
                                       x-on:click="if (dragging) { $event.preventDefault(); dragging = false }"
                                       x-on:keydown.alt.arrow-right.prevent="$wire.reschedule({{ $item['id'] }}, 1)"
                                       x-on:keydown.alt.arrow-left.prevent="$wire.reschedule({{ $item['id'] }}, -1)"
                                       x-bind:class="{ 'is-dragging': dragging }"
                                   @endif
                                >
                                    <span>{{ $item['label'] }} · {{ $item['title'] }}</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="uls-gantt-row"><div class="uls-gantt-side uls-gantt-empty">Nothing is published or scheduled in these dates.</div><div class="uls-gantt-track"></div></div>
            @endforelse

            @if ($todayX !== null)
                <div class="uls-gantt-today" style="left: calc(var(--side) + {{ $todayX }}px)"><span>Today</span></div>
            @endif
        </div>

        <ul class="uls-gantt-legend" aria-label="Legend">
            @foreach ($states as [$label, $color])
                <li><span class="uls-swatch" style="--c: {{ $color }}" aria-hidden="true"></span>{{ $label }}</li>
            @endforeach
            <li><span class="uls-swatch uls-swatch-open" aria-hidden="true"></span>No take-down date</li>
            @if ($canPublish)<li class="uls-gantt-hint">Drag a bar, or focus it and press Alt + ← / →, to move it by days.</li>@endif
        </ul>

        @if ($unscheduled->isNotEmpty())
            <section class="uls-gantt-backlog" aria-labelledby="backlog-heading">
                <h2 id="backlog-heading">Not scheduled yet</h2>
                <ul>
                    @foreach ($unscheduled as $story)
                        <li>
                            <span class="uls-dot" style="--c: {{ \App\Models\News::STATES[$story->status][1] }}" aria-hidden="true"></span>
                            <a href="{{ \App\Filament\Resources\News\NewsResource::getUrl('edit', ['record' => $story]) }}">{{ $story->title }}</a>
                            <span>{{ \App\Models\News::STATES[$story->status][0] }} · edited {{ $story->updated_at->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-filament-panels::page>
