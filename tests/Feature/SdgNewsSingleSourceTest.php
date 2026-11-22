<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\News;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * News and SDG pages read the same published News records: one publication of each article, the same counts
 * everywhere, and every change visible on every page (page cache included).
 */
class SdgNewsSingleSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SiteContentSeeder::class); // labels, the 17 SDGs, page cache on (10 minutes)
    }

    private function story(string $slug, array $extra = []): News
    {
        // Plain-text content: rich HTML goes through the sanitizer, which is tested in PublishingTest.
        return News::create($extra + [
            'title' => "Story $slug", 'slug' => $slug, 'category' => 'research', 'content' => 'Body',
            'status' => 'published', 'published_at' => now()->subDay(), 'sdgs' => [4],
        ]);
    }

    public function test_a_published_sdg_4_story_is_on_every_page_and_unpublished_ones_on_none(): void
    {
        $this->story('live-story');
        $this->story('a-draft', ['status' => 'draft']);
        $this->story('in-review', ['status' => 'review']);
        $this->story('archived', ['status' => 'archived']);
        $this->story('scheduled', ['status' => 'scheduled', 'published_at' => now()->addDay()]);
        $this->story('taken-down', ['unpublish_at' => now()->subHour()]);

        $pages = [route('news.index'), route('news.index', ['sdg' => 4]), route('sdgs.show', 4), route('academics.index')];
        foreach ($pages as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('Story live-story', $html, "missing on $url");
            foreach (['a-draft', 'in-review', 'archived', 'scheduled', 'taken-down'] as $hidden) {
                $this->assertStringNotContainsString("Story $hidden", $html, "$hidden shown on $url");
            }
        }

        $this->get(route('news.show', 'live-story'))->assertOk();
        foreach (['a-draft', 'in-review', 'archived', 'scheduled', 'taken-down'] as $hidden) {
            $this->get(route('news.show', $hidden))->assertNotFound();
        }
    }

    public function test_the_story_count_is_the_same_on_the_news_and_sdg_pages(): void
    {
        $college = College::create(['name' => 'College of Test', 'slug' => 'college-of-test', 'status' => 'published',
            'extension_projects' => [['title' => 'Reading camp', 'sdgs' => [4, '4', 10]]]]);
        foreach (range(1, 8) as $i) {
            $this->story("s$i", ['published_at' => now()->subDays($i), 'college_id' => $i <= 3 ? $college->id : null]);
        }
        $this->story('tagged-twice', ['sdgs' => [4, '4', 5]]); // counts once
        $this->story('other-goal', ['sdgs' => [5]]);
        $this->story('a-draft', ['status' => 'draft']);

        $newsTotal = $this->get(route('news.index', ['sdg' => 4]))->assertOk()->viewData('news')->total();
        $goalPage = $this->get(route('sdgs.show', 4))->assertOk();
        $report = $this->get(route('sdgs.index'))->assertOk();

        $this->assertSame(9, $newsTotal);
        $this->assertSame($newsTotal, $goalPage->viewData('storyCount'));
        $this->assertSame($newsTotal, News::sdgStoryCount(4));
        $this->assertSame($newsTotal, $report->viewData('storyCounts')[4]);
        // Totals keep the college's extension project, shown separately (counted once despite "4" and 4).
        $this->assertSame(1, $report->viewData('projectCounts')[4]);
        $this->assertSame(10, $report->viewData('counts')[4]);
        $report->assertSee('9 stories · 1 project');
        // The college grid: its own 3 stories plus the project, each once.
        $this->assertSame(4, $college->fresh()->sdgStoryCounts()[4]);

        // The same words on both pages.
        $this->get(route('news.index', ['sdg' => 4]))->assertSee('· 9 stories', false);
        $goalPage->assertSee('9 stories');
    }

    public function test_changing_tags_or_publish_state_shows_on_every_page_at_once(): void
    {
        $story = $this->story('changing');

        // /sdgs/4 has no query string, so it goes through the page cache; the second visit is served from it.
        $this->get(route('sdgs.show', 4))->assertSee('Story changing');
        $this->get(route('sdgs.show', 4))->assertHeader('X-Page-Cache', 'HIT')->assertSee('Story changing');

        // A new SDG tag: gone from goal 4 and on goal 5, on the news and SDG pages alike.
        $story->update(['sdgs' => [5]]);
        $this->get(route('sdgs.show', 4))->assertDontSee('Story changing');
        $this->get(route('news.index', ['sdg' => 4]))->assertDontSee('Story changing');
        $this->get(route('sdgs.show', 5))->assertSee('Story changing');
        $this->get(route('news.index', ['sdg' => 5]))->assertSee('Story changing');

        // Back to draft: off every page, including the cached ones.
        $this->get(route('news.index'))->assertSee('Story changing');
        $story->update(['status' => 'draft']);
        foreach ([route('news.index'), route('sdgs.show', 5), route('academics.index'), route('sdgs.index')] as $url) {
            $this->get($url)->assertOk()->assertDontSee('Story changing');
        }
        $this->assertSame([], News::sdgStoryCounts());
    }

    public function test_sample_stories_use_the_same_model_and_pages(): void
    {
        $this->seed(\Database\Seeders\NewsDesignPreviewSeeder::class);
        $sample = News::where('title', 'like', \Database\Seeders\NewsDesignPreviewSeeder::TITLE_PREFIX . '%')->published()->first();
        $this->assertNotNull($sample, 'samples are ordinary published News records');

        $goal = \App\Support\Sdg::numbers($sample->sdgs)[0];
        $this->get(route('news.index', ['sdg' => $goal]))->assertSee($sample->title);
        $this->get(route('sdgs.show', $goal))->assertOk()->assertSee(e($sample->title), false);
        $this->get(route('academics.index'))->assertSee(e(News::published()->latest('published_at')->first()->title), false);

        // The removal command takes them (and their photos and thumbnails) off every page again.
        $this->artisan('news:remove-design-preview')->assertSuccessful();
        $this->assertSame(0, News::count());
        $this->get(route('sdgs.show', $goal))->assertDontSee(e($sample->title), false);
    }
}
