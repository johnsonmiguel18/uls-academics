<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AcademicEvents\AcademicEventResource;
use App\Filament\Resources\News\NewsResource;
use App\Models\News;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/**
 * Gantt-style view of the publishing schedule: each story is a bar from its publish date
 * to its take-down date, colored by status, grouped by college. Bars can be dragged
 * (or moved with Alt+arrow keys) to reschedule. Events show as bars too, for planning.
 */
class PublishingTimeline extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|\UnitEnum|null $navigationGroup = 'News & Events';

    protected static ?string $navigationLabel = 'Publishing timeline';

    protected static ?string $title = 'Publishing timeline';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.publishing-timeline';

    /** Zoom levels: [days shown, pixels per day, days moved by Previous/Next]. */
    public const ZOOMS = [
        '2w' => ['2 weeks', 14, 64, 7],
        '6w' => ['6 weeks', 42, 28, 14],
        '3m' => ['3 months', 91, 12, 28],
    ];

    public string $zoom = '6w';

    public int $shift = 0;

    public static function canAccess(): bool
    {
        return NewsResource::canViewAny();
    }

    public function previous(): void
    {
        $this->shift--;
    }

    public function next(): void
    {
        $this->shift++;
    }

    public function today(): void
    {
        $this->shift = 0;
    }

    /** First day shown: the Monday a week before today, moved by Previous/Next. */
    public function windowStart(): CarbonImmutable
    {
        return CarbonImmutable::today()->startOfWeek()->subWeek()->addDays($this->shift * self::ZOOMS[$this->zoom][3]);
    }

    public function windowEnd(): CarbonImmutable
    {
        return $this->windowStart()->addDays(self::ZOOMS[$this->zoom][1]);
    }

    /** Move a story's publish and take-down dates by whole days (drag or Alt+arrow). */
    public function reschedule(int $id, int $days): void
    {
        $news = NewsResource::getEloquentQuery()->find($id);

        if (! $news || ! NewsResource::canEdit($news) || ! (auth()->user()?->can('publish_news') ?? false)) {
            Notification::make()->title('You cannot reschedule this story')->danger()->send();

            return;
        }

        if ($days === 0 || ! $news->published_at) {
            return;
        }

        $news->published_at = $news->published_at->addDays($days);
        $news->unpublish_at = $news->unpublish_at?->addDays($days);
        $news->save(); // News::saving() turns it into "scheduled" or "published" from the new date

        Notification::make()
            ->title('Rescheduled: ' . $news->published_at->format('M j, Y g:i A'))
            ->body($news->title)
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        [, $days, $dayWidth] = self::ZOOMS[$this->zoom];
        $start = $this->windowStart();
        $end = $this->windowEnd();

        // Position on the timeline in px; bars that start before the window are clipped at 0.
        $x = fn ($date) => max(0, min($days, $start->diffInDays($date, false))) * $dayWidth;

        $news = NewsResource::getEloquentQuery()
            ->with('college:id,name')
            ->whereNotNull('published_at')
            ->where('published_at', '<', $end)
            ->where(fn ($q) => $q->whereNull('unpublish_at')->orWhere('unpublish_at', '>', $start))
            ->where('status', '!=', 'archived')
            ->orderBy('published_at')
            ->get()
            ->map(function (News $n) use ($x, $start, $end, $dayWidth) {
                $state = $n->state();

                return [
                    'id' => $n->id,
                    'type' => 'news',
                    'title' => $n->title,
                    'group' => $n->college?->name ?? 'University-wide',
                    'state' => $state,
                    'label' => News::STATES[$state][0],
                    'color' => News::STATES[$state][1],
                    'left' => $x($n->published_at),
                    'width' => max($dayWidth / 2, $x($n->unpublish_at ?? $end) - $x($n->published_at)),
                    'openStart' => $n->published_at->lt($start),
                    'openEnd' => ! $n->unpublish_at || $n->unpublish_at->gt($end),
                    'when' => $n->published_at->format('M j, g:i A') . ($n->unpublish_at ? ' – ' . $n->unpublish_at->format('M j') : ' onward'),
                    'url' => NewsResource::getUrl('edit', ['record' => $n]),
                    'movable' => NewsResource::canEdit($n) && (auth()->user()?->can('publish_news') ?? false),
                ];
            });

        $events = AcademicEventResource::canViewAny()
            ? AcademicEventResource::getEloquentQuery()
                ->with('college:id,name')
                ->where('starts_at', '<', $end)
                ->where(fn ($q) => $q->where('ends_at', '>', $start)->orWhere(fn ($q) => $q->whereNull('ends_at')->where('starts_at', '>=', $start)))
                ->orderBy('starts_at')
                ->get()
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'type' => 'event',
                    'title' => $e->title,
                    'group' => $e->college?->name ?? 'University-wide',
                    'state' => 'event',
                    'label' => 'Event',
                    'color' => '#0F766E',
                    'left' => $x($e->starts_at),
                    'width' => max($dayWidth, $x(($e->ends_at ?? $e->starts_at)->endOfDay()) - $x($e->starts_at)),
                    'openStart' => $e->starts_at->lt($start),
                    'openEnd' => false,
                    'when' => $e->starts_at->format('M j, g:i A') . ($e->ends_at ? ' – ' . $e->ends_at->format('M j') : ''),
                    'url' => AcademicEventResource::getUrl('edit', ['record' => $e]),
                    'movable' => false,
                ])
            : collect();

        /** @var Collection $rows */
        $rows = $news->concat($events)->groupBy('group')->sortKeys();

        return [
            'days' => collect(range(0, $days - 1))->map(fn ($i) => $start->addDays($i)),
            'dayWidth' => $dayWidth,
            'width' => $days * $dayWidth,
            'todayX' => CarbonImmutable::now()->between($start, $end) ? $start->diffInMinutes(now()) / 1440 * $dayWidth : null,
            'groups' => $rows,
            'start' => $start,
            'end' => $end->subDay(),
            'unscheduled' => NewsResource::getEloquentQuery()
                ->whereIn('status', ['draft', 'review'])
                ->latest('updated_at')
                ->limit(12)
                ->get(['id', 'title', 'status', 'updated_at']),
            'counts' => NewsResource::getEloquentQuery()->get(['status', 'published_at', 'unpublish_at'])
                ->countBy(fn (News $n) => $n->state()),
        ];
    }
}
