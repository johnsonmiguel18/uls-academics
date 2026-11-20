<?php

namespace Database\Seeders;

use App\Models\News;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Sample stories for showing the news front page design before real content is added.
 *
 *   php artisan db:seed --class=NewsDesignPreviewSeeder     load (safe to run again: updates the same samples)
 *   php artisan news:remove-design-preview                  remove every sample and its images
 *
 * Every sample has a title starting with "[SAMPLE] " and a slug starting with "sample-"; real stories are
 * never changed. Samples are published, so load them on a local or staging copy, not on the live site.
 * Not part of DatabaseSeeder on purpose.
 */
class NewsDesignPreviewSeeder extends Seeder
{
    public const TITLE_PREFIX = '[SAMPLE] ';

    public const SLUG_PREFIX = 'sample-';

    /** Copies of bundled photos, under storage/app/public (the "public" disk). */
    public const IMAGE_DIR = 'design-preview';

    /** [slug, title, category, SDGs, photo in public/media/photos or null, short summary or null] */
    private const STORIES = [
        ['solar-water-pump', 'Engineering students build a solar-powered water pump for a farming village', 'extension', [6, 7, 9, 13, 17], 'engineering.jpg',
            'Fourth-year engineering students designed and installed a solar pump that now brings clean water to forty households.'],
        ['nursing-licensure', 'Nursing graduates post a high passing rate in the licensure exam', 'achievement', [3, 4], 'nursing.jpg',
            'Most first-time takers passed the November exam, well above the national rate.'],
        ['library-hours', 'Library hours extended', 'announcement', [4], null, null],
        ['it-internships', 'College of IT signs an internship agreement with a software company', 'linkages', [4, 8, 9, 17], 'it.jpg',
            'Students will join paid six-month internships in software testing and web development.'],
        ['blood-drive', 'Medical technology interns join the city blood donation drive', 'extension', [3], 'medlab.jpg',
            'Interns screened more than three hundred donors over two days.'],
        ['reading-study', 'Teacher education faculty share a two-year study on reading fluency among Grade 3 learners in rural schools', 'research', [4], 'teacher.jpg',
            'Daily paired reading raised fluency scores by a fifth in one school year.'],
        ['criminology-accreditation', 'Criminology program earns a higher accreditation level', 'achievement', [4, 16], 'criminology.jpg',
            'The visiting team cited the new forensic laboratory and community partnerships.'],
        ['lantern-contest', 'Campus lantern contest', 'college-news', [11], null, null],
        ['quiz-bee', 'Accountancy students win the regional financial reporting quiz', 'achievement', [4, 8], 'accounting.jpg',
            'The three-member team placed first among sixteen schools.'],
        ['irrigation-training', 'Extension team trains farmers on water-saving irrigation', 'extension', [2, 6, 12, 13, 15], 'volunteers.jpg',
            'Rice farmers learned a method that cuts water use by up to a quarter.'],
        ['evening-mba', 'Graduate school opens evening classes for working professionals', 'announcement', [4, 8], 'graduation.jpg',
            'Classes meet on two weekday evenings and on Saturday mornings.'],
        ['weekend-market', 'Business students run a weekend market for local producers', 'extension', [8, 11, 12], 'business.jpg',
            'Twenty producers from nearby villages sold vegetables, rice and crafts on campus.'],
        ['clinic-survey', 'Public health students survey clinic waiting times', 'research', [3, 4], null, null],
        ['legal-aid', 'Law students open a free legal aid desk', 'extension', [10, 16], 'law.jpg',
            'Supervised by practising lawyers, the desk answers questions every Wednesday.'],
        ['reading-corner', 'New reading corner for first-year students', 'college-news', [4], 'library.jpg',
            'Quiet seats, charging points and a shelf of short reads.'],
        ['river-study', 'Science students map water quality along the river', 'research', [6, 13, 14], 'science.jpg',
            'Samples from twelve points show where runoff enters the river.'],
    ];

    public function run(): void
    {
        $created = $updated = $skipped = 0;

        foreach (self::STORIES as $i => [$slug, $title, $category, $sdgs, $photo, $summary]) {
            $slug = self::SLUG_PREFIX . $slug;
            $story = News::where('slug', $slug)->first();

            // A real story that happens to use this slug is left alone.
            if ($story && ! str_starts_with($story->title, self::TITLE_PREFIX)) {
                $this->command?->warn("Skipped $slug: a real story already uses this address.");
                $skipped++;

                continue;
            }

            $story ??= new News(['slug' => $slug]);
            $story->exists ? $updated++ : $created++;

            $story->fill([
                'title' => self::TITLE_PREFIX . $title,
                'category' => $category,
                'college_id' => null, // samples stay off the college pages
                'sdgs' => $sdgs,
                'excerpt' => $summary,
                'content' => $this->body($summary),
                'featured_image' => $photo ? $this->image($photo) : null,
                'status' => 'published',
                'is_featured' => $i === 0,
                // The featured sample is the newest story, so it is the one the front page shows.
                'published_at' => $i === 0 ? now()->subMinutes(30) : now()->subDays($i),
                'unpublish_at' => null,
            ])->save();
        }

        $this->command?->info("Sample stories: $created added, $updated updated" . ($skipped ? ", $skipped skipped" : '') . '.');
    }

    /** Plain paragraphs, so the article page reads like a full story. */
    private function body(?string $summary): string
    {
        return implode("\n\n", array_filter([
            'This is sample text for the design preview. It will be replaced by real stories after the deans approve the layout.',
            $summary,
            'A second paragraph shows how a longer article reads on its own page. It has enough words to fill several lines on a phone screen, so the spacing, line length and type size can be judged.',
            'A closing paragraph, as most stories have one: who to contact for more information, or what happens next.',
        ]));
    }

    /** Copies a bundled photo to the uploads folder once and returns its path on the "public" disk. */
    private function image(string $photo): string
    {
        $path = self::IMAGE_DIR . '/' . $photo;
        $target = storage_path('app/public/' . $path);

        if (! is_file($target)) {
            File::ensureDirectoryExists(dirname($target));
            File::copy(public_path('media/photos/' . $photo), $target);
        }

        return $path;
    }
}
