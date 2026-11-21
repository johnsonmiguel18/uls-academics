<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Keyboard access that the page markup must provide (checked in a browser with axe-core as well). */
class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_starts_with_a_skip_link_to_a_focusable_content_area(): void
    {
        $this->seed(\Database\Seeders\SiteContentSeeder::class);

        foreach (['/', '/programs', '/news', '/sdgs', '/global'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertMatchesRegularExpression('/<a class="ac-skip" href="#content">[^<]+<\/a>/', $html, $url);
            $this->assertStringContainsString('<div id="content" tabindex="-1">', $html, $url);
        }
    }

    public function test_screen_readers_get_distinct_names_and_no_repeated_labels(): void
    {
        $this->seed(\Database\Seeders\SiteContentSeeder::class);
        foreach (range(1, 12) as $i) {
            \App\Models\News::create(['title' => "Story $i", 'slug' => "story-$i", 'category' => 'research', 'content' => 'Body',
                'status' => 'published', 'sdgs' => [2, 6, 12, 13, 15], 'published_at' => now()->subDays($i)]);
        }

        $html = $this->get('/news')->assertOk()->getContent();

        // The header's program search is a named search area (pages may have their own search too).
        $this->assertStringContainsString('role="search" aria-label="Search programs"', $html);
        // Page numbers are a named navigation.
        $this->assertStringContainsString('<nav aria-label="Pages">', $html);
        // SDG labels carry the goal name once (hidden text), not again as a tooltip.
        $this->assertStringContainsString('<span class="sr-only">: Zero Hunger</span>', $html);
        $this->assertDoesNotMatchRegularExpression('/class="news-sdg"[^>]*title=/', $html);
        $this->assertDoesNotMatchRegularExpression('/<li class="news-sdg-more"[^>]*title=/', $html);
    }
}
