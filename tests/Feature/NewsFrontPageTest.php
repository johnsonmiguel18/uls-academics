<?php

namespace Tests\Feature;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** News front page (/news): hero, card grid, sidebar rows, SDG labels and filters; prev/next on the article. */
class NewsFrontPageTest extends TestCase
{
    use RefreshDatabase;

    private function story(string $slug, int $daysAgo, array $extra = []): News
    {
        // Plain-text content: rich HTML goes through the sanitizer, which is tested in PublishingTest.
        return News::create($extra + [
            'title' => "Story $slug", 'slug' => $slug, 'category' => 'college-news', 'content' => 'Body',
            'status' => 'published', 'published_at' => now()->subDays($daysAgo),
        ]);
    }

    public function test_without_a_featured_story_the_newest_story_is_the_hero_and_not_repeated(): void
    {
        $this->story('newest', 1);
        $this->story('older', 2);

        $response = $this->get(route('news.index'))->assertOk();
        $this->assertSame('newest', $response->viewData('featuredNews')->slug);
        $this->assertSame(['older'], $response->viewData('news')->pluck('slug')->all());
    }

    public function test_featured_story_wins_and_stays_off_later_pages(): void
    {
        $this->story('newest', 1);
        $this->story('chosen', 5, ['is_featured' => true]);
        foreach (range(1, 4) as $i) {
            $this->story("old-$i", 10 + $i);
        }

        $this->assertSame('chosen', $this->get(route('news.index'))->viewData('featuredNews')->slug);

        // Page 2 has no hero and still leaves the featured story out of the grid.
        $page2 = $this->get(route('news.index', ['page' => 2]));
        $this->assertNull($page2->viewData('featuredNews'));
        $this->assertNotContains('chosen', $page2->viewData('news')->pluck('slug'));
    }

    public function test_filtered_lists_have_no_hero_and_keep_every_match(): void
    {
        $this->story('water', 1, ['sdgs' => [6]]);

        $response = $this->get(route('news.index', ['sdg' => 6]))->assertOk();
        $this->assertNull($response->viewData('featuredNews'));
        $this->assertSame(['water'], $response->viewData('news')->pluck('slug')->all());
    }

    public function test_sidebar_rows_skip_the_hero_and_the_cards_on_the_page(): void
    {
        foreach (range(1, 12) as $i) {
            $this->story("s-$i", $i);
        }

        $response = $this->get(route('news.index'));
        $shown = $response->viewData('news')->pluck('slug')->push($response->viewData('featuredNews')->slug);
        $rows = $response->viewData('latestNews')->pluck('slug');

        $this->assertNotEmpty($rows);
        $this->assertEmpty($rows->intersect($shown));
    }

    public function test_the_lead_story_lists_the_next_three_headlines_and_the_cards_continue(): void
    {
        foreach (range(1, 12) as $i) {
            $this->story("s-$i", $i);
        }

        $html = $this->get(route('news.index'))->assertOk()->getContent();
        preg_match('/<ul class="news-lead-related">(.*?)<\/ul>/s', $html, $related);
        preg_match('/<div class="news-cards news-rail".*?<\/div>\s*<nav/s', $html, $grid);

        // Hero s-1; the next three (s-2 to s-4) as headlines under it; the cards start at s-5 (6 cards = 9 per page).
        $this->assertSame(3, substr_count($related[1], '<li>'));
        $this->assertStringContainsString('Story s-2', $related[1]);
        $this->assertStringContainsString('Story s-4', $related[1]);
        $this->assertSame(6, substr_count($grid[0], 'class="news-card "'));
        $this->assertStringContainsString('Story s-5', $grid[0]);
        $this->assertStringNotContainsString('Story s-4', $grid[0]);

        // The rail's arrow buttons (shown by the site script on phones) point at the rail and carry their labels.
        $this->assertStringContainsString('id="news-rail"', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*data-rail="prev"[^>]*aria-controls="news-rail"[^>]*aria-label="Previous stories"[^>]*hidden>/', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*data-rail="next"[^>]*aria-controls="news-rail"[^>]*aria-label="Next stories"[^>]*hidden>/', $html);

        // Filtered lists have no lead, so no headline list either.
        $this->assertStringNotContainsString('news-lead-related', $this->get(route('news.index', ['category' => 'college-news']))->getContent());
    }

    public function test_sdg_labels_link_to_the_filter_and_overflow_after_three(): void
    {
        $this->story('hero', 1);
        $this->story('many', 2, ['sdgs' => [2, 6, 12, 13, 15]]);

        $this->get(route('news.index'))->assertOk()
            ->assertSee(route('news.index', ['sdg' => 2]), false)
            ->assertSee(route('news.index', ['sdg' => 12]), false)
            ->assertSee('+2')
            // The filter row offers all 17 goals and keeps the chosen category.
            ->assertSee(route('news.index', ['sdg' => 17]), false);

        $this->get(route('news.index', ['category' => 'research']))
            ->assertSee(e(route('news.index', ['category' => 'research', 'sdg' => 4])), false);
    }

    public function test_article_links_to_previous_and_next_story(): void
    {
        $this->story('first', 3);
        $this->story('middle', 2, ['sdgs' => [4]]);
        $this->story('last', 1);

        $this->get(route('news.show', 'middle'))->assertOk()
            ->assertSee(route('news.show', 'first'))
            ->assertSee(route('news.show', 'last'))
            ->assertSee('Previous story')
            ->assertSee('Next story')
            ->assertSee(route('news.index', ['sdg' => 4]), false);
    }
}
