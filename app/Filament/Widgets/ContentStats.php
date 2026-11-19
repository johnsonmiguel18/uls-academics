<?php

namespace App\Filament\Widgets;

use App\Models\ActivityLog;
use App\Models\News;
use App\Models\Program;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentStats extends StatsOverviewWidget
{
    protected static ?int $sort = -5;

    protected static bool $isLazy = false;

    /** University-wide numbers: not for college admins. */
    public static function canView(): bool
    {
        return auth()->user()?->can('manage_academics') ?? false;
    }

    protected function getStats(): array
    {
        // Activity per day for the last 7 days, oldest first (one grouped query, not seven).
        $perDay = ActivityLog::where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $activity = collect(range(6, 0))
            ->map(fn (int $daysAgo) => (int) ($perDay[now()->subDays($daysAgo)->toDateString()] ?? 0))
            ->all();

        $programsByStatus = Program::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            Stat::make('Published programs', $programsByStatus['published'] ?? 0)
                ->description(($programsByStatus['draft'] ?? 0) . ' drafts')
                ->descriptionIcon(Heroicon::OutlinedAcademicCap)
                ->color('primary'),

            Stat::make('News & announcements', News::published()->count())
                ->description('Latest: ' . (News::published()->latest('published_at')->value('title') ?? 'none yet'))
                ->descriptionIcon(Heroicon::OutlinedNewspaper)
                ->color('primary'),

            Stat::make('Global partners', \App\Http\Controllers\GlobalController::partners()->count())
                ->description(\App\Models\PartnershipInquiry::where('status', 'new')->count() . ' new inquiries')
                ->descriptionIcon(Heroicon::OutlinedLink)
                ->url(\App\Filament\Resources\PartnershipInquiries\PartnershipInquiryResource::getUrl())
                ->color('primary'),

            Stat::make('Admin users', User::count())
                ->description('Admins and college representatives')
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('primary'),

            Stat::make('Activity this week', array_sum($activity))
                ->description('Logins and content changes')
                ->descriptionIcon(Heroicon::OutlinedClipboardDocumentList)
                ->chart($activity)
                ->color('primary'),
        ];
    }
}
