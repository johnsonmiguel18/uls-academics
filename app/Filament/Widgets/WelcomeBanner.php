<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\News\NewsResource;
use App\Filament\Resources\Programs\ProgramResource;
use Filament\Widgets\Widget;

class WelcomeBanner extends Widget
{
    protected static ?int $sort = -10;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.welcome-banner';

    protected function getViewData(): array
    {
        $user = auth()->user();
        $hour = now()->hour;

        return [
            'greeting' => $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening'),
            'name' => strtok((string) $user?->name, ' ') ?: 'there',
            'role' => $user?->getRoleNames()->first(),
            'newProgramUrl' => ProgramResource::canCreate() ? ProgramResource::getUrl('create') : null,
            'newNewsUrl' => NewsResource::canCreate() ? NewsResource::getUrl('create') : null,
        ];
    }
}
