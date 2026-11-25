<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\News;
use App\Support\Site;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** News search and sharing fields (generated unless typed), Literata Light, and the home page news layout. */
class NewsSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SiteContentSeeder::class);
    }

    private function story(array $extra = []): News
    {
        return News::create($extra + [
            'title' => 'Engineering students build a solar water pump for Barangay Rizal', 'slug' => 'solar-pump',
            'category' => 'extension', 'excerpt' => 'Students installed a solar pump that supplies 40 households.',
            'content' => 'The solar pump was designed by engineering students. The pump now serves the barangay.',
            'status' => 'published', 'published_at' => now()->subDay(), 'sdgs' => [6, 7],
        ]);
    }

    public function test_empty_fields_are_generated_from_the_story(): void
    {
        $college = College::create(['name' => 'College of Engineering and Architecture', 'short_name' => 'CEA', 'slug' => 'cea', 'status' => 'published']);
        $news = $this->story(['college_id' => $college->id]);

        $this->assertSame('Engineering students build a solar water pump for Barangay Rizal', $news->meta_title);
        $this->assertSame('Students installed a solar pump that supplies 40 households.', $news->meta_description);
        $this->assertSame($news->title, $news->og_title);
        $this->assertSame(['Extension', 'CEA', 'SDG 6: Clean Water and Sanitation', 'SDG 7: Affordable and Clean Energy'], $news->tags);
        $this->assertStringStartsWith('Extension, CEA, ', $news->keywords);
        $this->assertStringContainsString('pump', $news->keywords);
        $this->assertStringContainsString('Clean Water and Sanitation', $news->keywords);
        $this->assertLessThanOrEqual(255, mb_strlen($news->keywords));
    }

    public function test_generated_values_follow_edits_and_typed_values_are_kept(): void
    {
        $news = $this->story();
        $news->update(['meta_description' => 'Written by the editor.']);
        $news->update(['title' => 'Solar pump now serves Barangay Rizal', 'excerpt' => 'A new summary.']);

        $this->assertSame('Solar pump now serves Barangay Rizal', $news->meta_title, 'generated: follows the new title');
        $this->assertSame('Solar pump now serves Barangay Rizal', $news->og_title);
        $this->assertSame('A new summary.', $news->og_description);
        $this->assertSame('Written by the editor.', $news->meta_description, 'typed: kept');

        $long = str_repeat('Longer headline words ', 6);
        $news->update(['title' => $long]);
        $this->assertLessThanOrEqual(70, mb_strlen($news->meta_title));
        $this->assertStringEndsWith('words…', $news->meta_title, 'cut at a word boundary');
    }

    public function test_the_article_head_has_the_search_and_sharing_tags(): void
    {
        $news = $this->story(['og_title' => 'Share this: a solar pump']);
        News::whereKey($news->id)->update(['keywords' => null]); // saved before the fields existed: generated on the page

        $this->get(route('news.show', $news->slug))->assertOk()
            ->assertSee('<title>Engineering students build a solar water pump for Barangay Rizal | ', false)
            ->assertSee('<meta name="description" content="Students installed a solar pump that supplies 40 households.">', false)
            ->assertSee('<meta property="og:title" content="Share this: a solar pump">', false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('<meta name="keywords" content="Extension, ', false)
            ->assertSee('<meta property="article:tag" content="SDG 6: Clean Water and Sanitation">', false)
            ->assertSee('"@type":"NewsArticle"', false);

        $css = file_get_contents(public_path('css/news.css'));
        $this->assertStringContainsString('font-family: "Literata"', $css);
        foreach (['latin-300-normal', 'latin-300-italic', 'latin-600-normal', 'latin-ext-300-normal'] as $file) {
            $this->assertFileExists(public_path("fonts/literata/literata-$file.woff2"));
        }
    }

    public function test_a_lone_story_has_no_empty_message_under_it_and_a_description(): void
    {
        $news = $this->story(['excerpt' => null, 'content' => '']);

        foreach ([route('academics.index'), route('news.index')] as $url) {
            $this->get($url)->assertOk()->assertSee('news-lead-story', false)->assertDontSee('news-empty-state', false);
        }
        $this->get(route('news.index', ['search' => 'nothing-matches-this']))->assertSee('news-empty-state', false);

        $default = Site::text('seo.default_description');
        $this->get(route('news.show', $news->slug))->assertSee('<meta name="description" content="'.e($default).'">', false);
    }

    public function test_the_home_page_shows_news_in_the_news_page_layout(): void
    {
        foreach (range(1, 12) as $i) {
            $this->story(['title' => "Story $i", 'slug' => "story-$i", 'published_at' => now()->subDays($i), 'is_featured' => $i === 5]);
        }

        $html = $this->get(route('academics.index'))->assertOk()->getContent();
        $this->assertStringContainsString('class="news-front has-aside"', $html);
        $this->assertMatchesRegularExpression('~news-lead-story.*?<h4><a href="[^"]*/news/story-5">Story 5</a></h4>~s', $html, 'featured story leads');
        $this->assertStringContainsString('class="news-lead-related"', $html);
        $this->assertStringContainsString('id="home-news-rail"', $html);
        $this->assertStringContainsString('id="home-news-latest-heading"', $html);
        $this->assertSame(1, substr_count($html, '>Story 1<'), 'each story once');
    }
}
