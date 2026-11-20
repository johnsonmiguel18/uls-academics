<?php

namespace App\Console\Commands;

use App\Http\Controllers\ThumbnailController;
use App\Models\News;
use Database\Seeders\NewsDesignPreviewSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/** Undoes NewsDesignPreviewSeeder: deletes the sample stories, their photo copies and thumbnails. */
class RemoveNewsDesignPreview extends Command
{
    protected $signature = 'news:remove-design-preview';

    protected $description = 'Delete the [SAMPLE] news stories added for the design preview, and their images';

    public function handle(): int
    {
        // Both markers must match, so a real story is never deleted.
        $samples = News::where('slug', 'like', NewsDesignPreviewSeeder::SLUG_PREFIX . '%')
            ->where('title', 'like', NewsDesignPreviewSeeder::TITLE_PREFIX . '%')
            ->get();

        // One by one, so each deletion goes to the activity log and refreshes cached pages.
        $samples->each->delete();

        File::deleteDirectory(storage_path('app/public/' . NewsDesignPreviewSeeder::IMAGE_DIR));
        foreach (ThumbnailController::WIDTHS as $width) {
            File::deleteDirectory(public_path("thumbs/$width/storage/" . NewsDesignPreviewSeeder::IMAGE_DIR));
        }

        $this->info("Removed {$samples->count()} sample stor" . ($samples->count() === 1 ? 'y' : 'ies') . ' and their images.');

        return self::SUCCESS;
    }
}
