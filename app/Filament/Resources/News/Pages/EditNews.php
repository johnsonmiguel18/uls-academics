<?php

namespace App\Filament\Resources\News\Pages;

use App\Filament\Resources\News\NewsResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Support\Icons\Heroicon;
use Filament\Resources\Pages\EditRecord;

class EditNews extends EditRecord
{
    protected static string $resource = NewsResource::class;

    // Save and Cancel stay in view while scrolling a long story.
    public static bool $formActionsAreSticky = true;

    /** Under the title: what visitors see now, the record number and the story's web address. */
    public function getSubheading(): string
    {
        $state = $this->record->state();
        $label = \App\Models\News::STATES[$state][0] ?? ucfirst($state);

        return "{$label} · Story #{$this->record->getKey()} · /news/{$this->record->slug}";
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();
        $record = $this->record;

        // Without publishing permission: move a draft to review and back, nothing else.
        if (! ($user?->can('publish_news') ?? false)) {
            $data['status'] = in_array($record->status, ['draft', 'review']) && in_array($data['status'] ?? null, ['draft', 'review'])
                ? $data['status']
                : $record->status;
            $data['published_at'] = $record->published_at;
            $data['unpublish_at'] = $record->unpublish_at;
            $data['is_featured'] = $record->is_featured;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [

            Action::make('view')
                ->label('View on website')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (): string => route('news.show', $this->record->slug))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->state() === 'live'),

            DeleteAction::make()
                ->outlined()
                ->visible(fn (): bool =>
                    auth()->user()?->can('delete_news') ?? false
                )
                ->authorize(fn (): bool =>
                    auth()->user()?->can('delete_news') ?? false
                )
                ->requiresConfirmation(),
        ];
    }
}
