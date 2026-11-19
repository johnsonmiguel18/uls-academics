<?php

namespace Tests\Feature;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** A fresh install (settings and lists only, no content) and bare-minimum stories render every public page. */
class EmptySiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SiteContentSeeder::class);
    }

    public function test_every_public_page_renders_without_content(): void
    {
        foreach (['/', '/programs', '/news', '/news?sdg=4', '/news?search=nothing', '/sdgs', '/sdgs/1', '/sdgs/17', '/global'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/sdgs/18')->assertNotFound();
        $this->get('/news/missing')->assertNotFound();
        $this->getJson('/search/suggestions?q=ab')->assertOk()->assertExactJson([]);
    }

    public function test_a_story_with_only_the_required_fields_renders_everywhere(): void
    {
        // No image, summary, college or SDGs; published without a date.
        $story = News::create(['title' => 'Bare story', 'slug' => 'bare', 'category' => 'college-news', 'content' => 'Body', 'status' => 'published']);
        News::create(['title' => 'Older bare story', 'slug' => 'older', 'category' => 'unknown-category', 'content' => 'Body', 'status' => 'published', 'published_at' => now()->subDay()]);

        $this->get('/news')->assertOk()->assertSee('Bare story')->assertSee('Older bare story');
        $this->get('/news/bare')->assertOk()->assertSee('Bare story');
        $this->get('/news/older')->assertOk();
        $this->get('/')->assertOk();
        $this->get('/sdgs')->assertOk();
        $this->assertNotNull($story->fresh()->published_at);
    }
}
