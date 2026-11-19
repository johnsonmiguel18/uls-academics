<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;

/**
 * Marks scheduled stories as published once their time has come, so the admin list
 * shows the right status. The website itself already shows them on time (News::published()).
 */
class PublishScheduledNews extends Command
{
    protected $signature = 'news:publish-scheduled';

    protected $description = 'Mark scheduled news whose publication date has passed as published';

    public function handle(): int
    {
        $due = News::where('status', 'scheduled')->where('published_at', '<=', now())->get();

        // One by one, so each change goes to the activity log.
        $due->each->update(['status' => 'published']);

        $this->info("Published {$due->count()} scheduled stor" . ($due->count() === 1 ? 'y' : 'ies') . '.');

        return self::SUCCESS;
    }
}
