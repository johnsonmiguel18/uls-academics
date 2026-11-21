<?php

namespace Tests\Feature;

use App\Models\News;
use Database\Seeders\NewsDesignPreviewSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Sample stories for the design preview: safe to run twice, never touch real stories, fully removable. */
class NewsDesignPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->artisan('news:remove-design-preview');
        parent::tearDown();
    }

    public function test_samples_load_twice_without_duplicates_and_leave_real_stories_alone(): void
    {
        $this->seed(\Database\Seeders\SiteContentSeeder::class);
        $real = News::create(['title' => 'Real story', 'slug' => 'real-story', 'category' => 'research', 'content' => 'Body',
            'status' => 'published', 'is_featured' => true, 'published_at' => now()->subDays(40)]);
        // A real story that happens to use a sample address is skipped, not overwritten.
        $clash = News::create(['title' => 'Real clash', 'slug' => 'sample-library-hours', 'category' => 'research', 'content' => 'Body', 'status' => 'draft']);

        $this->seed(NewsDesignPreviewSeeder::class);
        $this->seed(NewsDesignPreviewSeeder::class);

        $samples = News::where('title', 'like', '[SAMPLE] %')->get();
        $this->assertCount(15, $samples);
        $this->assertSame(1, $samples->where('is_featured', true)->count());
        $this->assertTrue($samples->contains(fn ($s) => count($s->sdgs) > 3));
        $this->assertTrue($samples->contains(fn ($s) => $s->featured_image === null));
        $this->assertGreaterThanOrEqual(5, $samples->pluck('category')->unique()->count());
        $this->assertSame($real->only('title', 'is_featured', 'status'), $real->fresh()->only('title', 'is_featured', 'status'));
        $this->assertSame('Real clash', $clash->fresh()->title);

        // The featured sample is the newest featured story, so it opens the front page; "+N" shows the overflow.
        $this->get(route('news.index'))->assertOk()
            ->assertViewHas('featuredNews', fn ($hero) => $hero->slug === 'sample-solar-water-pump')
            ->assertSee('+2');

        $this->artisan('news:remove-design-preview')->expectsOutputToContain('Removed 15 sample stories')->assertSuccessful();

        $this->assertSame(0, News::where('title', 'like', '[SAMPLE] %')->count());
        $this->assertNotNull($real->fresh());
        $this->assertNotNull($clash->fresh());
        $this->assertDirectoryDoesNotExist(storage_path('app/public/' . NewsDesignPreviewSeeder::IMAGE_DIR));
    }
}
