<?php

namespace App\Filament\Widgets;

use App\Models\WebVital;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dashboard: how fast the public site feels to real visitors over the last 7 days,
 * as the 75th percentile (the value Google uses). Green = good, orange = needs improvement, red = poor.
 */
class WebVitalsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Website speed, real visits (last 7 days)';

    protected ?string $description = 'Core Web Vitals, 75th percentile. Share of visits measured: Site settings → Admin and performance.';

    public static function canView(): bool
    {
        return auth()->user()?->can('manage_academics') ?? false;
    }

    protected function getStats(): array
    {
        $since = now()->subDays(7);
        $names = ['LCP' => 'Loading (LCP)', 'INP' => 'Responsiveness (INP)', 'CLS' => 'Visual stability (CLS)', 'TTFB' => 'Server response (TTFB)'];

        return collect($names)->map(function (string $label, string $metric) use ($since) {
            $all = WebVital::p75($metric, $since);

            if (! $all) {
                return Stat::make($label, '—')->description('No measurements yet');
            }

            [$good, $poor] = WebVital::THRESHOLDS[$metric];
            $format = fn (float $v) => $metric === 'CLS' ? number_format($v, 2) : number_format($v / 1000, 2) . ' s';
            $mobile = WebVital::p75($metric, $since, 'mobile');
            $desktop = WebVital::p75($metric, $since, 'desktop');

            return Stat::make($label, $format($all['value']))
                ->description(collect([
                    $mobile ? 'Phones ' . $format($mobile['value']) : null,
                    $desktop ? 'Computers ' . $format($desktop['value']) : null,
                    number_format($all['count']) . ' visits',
                ])->filter()->implode(' · '))
                ->color($all['value'] <= $good ? 'success' : ($all['value'] <= $poor ? 'warning' : 'danger'));
        })->values()->all();
    }
}
