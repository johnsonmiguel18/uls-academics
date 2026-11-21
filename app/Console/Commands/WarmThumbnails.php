<?php

namespace App\Console\Commands;

use App\Support\Thumb;
use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/** Makes every missing thumbnail now, so no visitor waits for one. Run after each deploy or bulk upload. */
class WarmThumbnails extends Command
{
    protected $signature = 'thumbs:warm {--source= : Only "storage" (uploads) or "media" (public/media)}';

    protected $description = 'Make the missing 480 and 960 px WebP copies of all JPEG and PNG images (uploads and public/media)';

    public function handle(): int
    {
        $made = 0;
        $images = 0;

        foreach (['storage' => storage_path('app/public'), 'media' => public_path('media')] as $source => $dir) {
            if (! is_dir($dir) || ($this->option('source') && $this->option('source') !== $source)) {
                continue;
            }
            foreach ((new Finder)->files()->in($dir)->name('/\.(jpe?g|png)$/i') as $file) {
                $images++;
                $made += Thumb::warm(str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePathname()), $source);
            }
        }

        $this->info("$images images checked, $made thumbnails made.");

        return self::SUCCESS;
    }
}
