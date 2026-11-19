<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('activity-logs:prune')->daily();
Schedule::command('news:publish-scheduled')->everyMinute()->withoutOverlapping();
// Keep 90 days of loading-speed measurements.
Schedule::call(fn () => \App\Models\WebVital::where('created_at', '<', now()->subDays(90))->delete())->daily()->name('prune-web-vitals');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
