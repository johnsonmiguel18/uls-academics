{{-- News editor, middle pane toolbar: Edit / Preview, and in Preview a desktop / phone width (like a page builder).
     State lives in the x-data on the middle pane (mode, device). --}}
<div class="uls-pe-toolbar">
    <div class="uls-pe-seg" role="group" aria-label="View">
        <button type="button" x-on:click="mode = 'edit'" x-bind:aria-pressed="mode === 'edit'">
            <x-filament::icon icon="heroicon-m-pencil" class="uls-pe-icon" /> Edit
        </button>
        <button type="button" x-on:click="mode = 'preview'" x-bind:aria-pressed="mode === 'preview'" @disabled(! $story)
                @if (! $story) title="Save the story first to preview it" @endif>
            <x-filament::icon icon="heroicon-m-eye" class="uls-pe-icon" /> Preview
        </button>
    </div>

    <div class="uls-pe-seg" role="group" aria-label="Preview width" x-show="mode === 'preview'" x-cloak>
        <button type="button" x-on:click="device = 'desktop'" x-bind:aria-pressed="device === 'desktop'" aria-label="Desktop">
            <x-filament::icon icon="heroicon-m-computer-desktop" class="uls-pe-icon" />
        </button>
        <button type="button" x-on:click="device = 'phone'" x-bind:aria-pressed="device === 'phone'" aria-label="Phone">
            <x-filament::icon icon="heroicon-m-device-phone-mobile" class="uls-pe-icon" />
        </button>
    </div>
</div>

@if ($story)
    <div class="uls-pe-preview" x-show="mode === 'preview'" x-cloak>
        <p class="uls-pe-preview-note">Preview of the last saved version, as visitors will see it. Save to update it.</p>
        <div class="uls-pe-preview-frame" x-bind:class="device === 'phone' && 'is-phone'">
            {{-- Loaded only when Preview is opened; reloaded after each save --}}
            <template x-if="mode === 'preview'">
                <iframe src="{{ route('admin.news.preview', ['id' => $story->getKey(), 'v' => $story->updated_at?->timestamp]) }}"
                        title="Preview of {{ $story->title }}" loading="lazy"></iframe>
            </template>
        </div>
    </div>
@endif
