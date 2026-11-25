<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\News;
use App\Models\Program;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Live search suggestions, and no readable XSRF-TOKEN cookie (reviewers, November 2026). */
class SearchAndCookiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SiteContentSeeder::class);
    }

    public function test_suggestions_list_published_programs_colleges_and_news(): void
    {
        $college = College::create(['name' => 'College of Nursing', 'short_name' => 'CON', 'slug' => 'con', 'status' => 'published']);
        $hidden = College::create(['name' => 'Nursing Draft College', 'slug' => 'draft', 'status' => 'draft']);
        Program::create(['name' => 'BS Nursing', 'slug' => 'bsn', 'college_id' => $college->id, 'level' => 'undergraduate', 'degree_type' => 'bachelor', 'status' => 'published']);
        Program::create(['name' => 'Nursing in a draft college', 'slug' => 'x', 'college_id' => $hidden->id, 'level' => 'undergraduate', 'degree_type' => 'bachelor', 'status' => 'published']);
        Program::create(['name' => 'MA Nursing (draft)', 'slug' => 'man', 'college_id' => $college->id, 'level' => 'graduate', 'degree_type' => 'master', 'status' => 'draft']);
        News::create(['title' => 'Nursing board results', 'slug' => 'board', 'category' => 'achievements', 'content' => 'x', 'status' => 'published', 'published_at' => now()->subDay()]);
        News::create(['title' => 'Nursing draft story', 'slug' => 'draft-story', 'category' => 'achievements', 'content' => 'x', 'status' => 'draft']);

        $this->getJson(route('search.suggestions', ['q' => 'nurs']))->assertOk()->assertExactJson([
            ['title' => 'BS Nursing', 'type' => 'Program', 'url' => route('academics.program', 'bsn')],
            ['title' => 'College of Nursing', 'type' => 'College', 'url' => route('academics.college', 'con')],
            ['title' => 'Nursing board results', 'type' => 'News', 'url' => route('news.show', 'board')],
        ]);
        $this->getJson(route('search.suggestions', ['q' => 'n']))->assertExactJson([]);
        // Cached by the browser only: the response also carries the session cookie, so no shared cache may keep it.
        $this->assertStringContainsString('private', $this->getJson(route('search.suggestions', ['q' => 'nurs']))->headers->get('Cache-Control'));
        $this->getJson(route('search.suggestions', ['q' => ['nurs']]))->assertExactJson([]);
    }

    public function test_search_boxes_are_set_up_for_suggestions(): void
    {
        $html = $this->get(route('academics.index'))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'data-suggest="'.route('search.suggestions').'"'), 'header and home search');
        $this->assertStringContainsString('data-suggest-results=":count suggestions.', $html);
    }

    public function test_pages_set_no_xsrf_cookie(): void
    {
        foreach ([route('academics.index'), route('news.index'), route('global.home'), '/admin/login'] as $url) {
            $response = $this->get($url);
            $this->assertNull($response->getCookie('XSRF-TOKEN', false), "XSRF-TOKEN cookie on $url");
        }
    }
}
