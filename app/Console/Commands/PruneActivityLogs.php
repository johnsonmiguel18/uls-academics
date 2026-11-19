<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class PruneActivityLogs extends Command
{
    protected $signature = 'activity-logs:prune';

    protected $description = 'Delete activity logs older than one year';

    public function handle(): int
    {
        $cutoff = now()->subYear();

        $deleted = ActivityLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} expired activity log(s).");

        return self::SUCCESS;
    }
}
