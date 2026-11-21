<?php

namespace App\Filament\Resources\News\Pages;

use App\Filament\Resources\News\NewsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNews extends CreateRecord
{
    protected static string $resource = NewsResource::class;

    public static bool $formActionsAreSticky = true;

    /** Three panes (stories, article, settings) need the full width. */
    public function getMaxContentWidth(): \Filament\Support\Enums\Width
    {
        return \Filament\Support\Enums\Width::Full;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // Users without publishing permission can save drafts or submit them for review.
        if (! ($user?->can('publish_news') ?? false)) {
            $data['status'] = ($data['status'] ?? null) === 'review' ? 'review' : 'draft';
            $data['published_at'] = null;
            $data['unpublish_at'] = null;
            $data['is_featured'] = false;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
