{{-- News editor, left pane: jump to another story, or start a new one (like a template list in a page builder). --}}
@php
    $stories = \App\Filament\Resources\News\NewsResource::getEloquentQuery()
        ->latest('updated_at')->take(15)->get(['id', 'title', 'status', 'published_at', 'unpublish_at', 'updated_at']);
@endphp
<nav class="uls-pe-list" aria-label="Stories">
    @if (\App\Filament\Resources\News\NewsResource::canCreate())
        <a class="uls-pe-list-new" href="{{ \App\Filament\Resources\News\NewsResource::getUrl('create') }}"
           @if (! $story) aria-current="page" @endif>
            <x-filament::icon icon="heroicon-m-plus" class="uls-pe-list-icon" /> New story
        </a>
    @endif
    <p class="uls-pe-list-heading">Recently edited</p>
    <ul>
        @foreach ($stories as $item)
            @php([$stateLabel, $stateColor] = \App\Models\News::STATES[$item->state()] ?? [ucfirst($item->status), '#6B7280'])
            <li>
                <a href="{{ \App\Filament\Resources\News\NewsResource::getUrl('edit', ['record' => $item]) }}"
                   @if ($story?->is($item)) aria-current="page" @endif>
                    <span class="uls-pe-list-title">{{ $item->title }}</span>
                    <span class="uls-pe-list-state"><span class="uls-dot" style="--c: {{ $stateColor }}" aria-hidden="true"></span>{{ $stateLabel }}</span>
                </a>
            </li>
        @endforeach
    </ul>
    <a class="uls-pe-list-all" href="{{ \App\Filament\Resources\News\NewsResource::getUrl('index') }}">All stories</a>
</nav>
