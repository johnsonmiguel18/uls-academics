<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\News;
use App\Models\Term;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** SDG report (/sdgs) and the page of each goal (/sdgs/{n}). */
class SdgPagesTest extends TestCase
{
    use RefreshDatabase;

    private function data(): College
    {
        $college = College::create([
            'name' => 'College of Engineering', 'slug' => 'coe', 'short_name' => 'COE', 'status' => 'published',
            'extension_projects' => [['title' => 'Barangay water survey', 'description' => 'Mapped water lines.', 'sdgs' => ['6', '11']]],
        ]);
        College::create(['name' => 'Hidden College', 'slug' => 'hidden', 'status' => 'draft', 'extension_projects' => [['title' => 'Secret project', 'sdgs' => [6]]]]);

        // Tags as the admin picker sends them (text): stored as numbers.
        News::create(['title' => 'Clean water for Dubinan', 'slug' => 'water', 'college_id' => $college->id, 'status' => 'published', 'published_at' => now()->subDay(), 'category' => 'news', 'content' => 'x', 'sdgs' => ['6', '6']]);
        News::create(['title' => 'Draft water story', 'slug' => 'draft', 'college_id' => $college->id, 'status' => 'draft', 'category' => 'news', 'content' => 'x', 'sdgs' => [6]]);

        return $college;
    }

    public function test_goals_without_stories_show_their_aim_instead_of_a_zero(): void
    {
        $this->seed(SiteContentSeeder::class); // the 17 goals with their aims
        News::create(['title' => 'Reading camp', 'slug' => 'camp', 'category' => 'extension', 'content' => 'x',
            'status' => 'published', 'published_at' => now()->subDay(), 'sdgs' => [4]]);

        $html = $this->get(route('sdgs.index'))->assertOk()->getContent();
        // One goal's tile: from its <li class="sdg-N..."> to the closing </li>.
        $tile = function (int $n) use ($html) {
            preg_match('~<li class="sdg-'.$n.'[" ].*?</li>~s', $html, $m);

            return $m[0] ?? '';
        };

        // Goal 1 has no stories: its aim and "No stories yet", no "0" badge.
        $aim = Term::where('type', 'sdg')->where('key', '1')->value('description');
        $this->assertNotEmpty($aim);
        $this->assertStringContainsString(e($aim), $tile(1));
        $this->assertStringContainsString('No stories yet', $tile(1));
        $this->assertStringNotContainsString('ac-sdg-count', $tile(1));

        // Goal 4 has a story: its count, no aim.
        $four = $tile(4);
        $this->assertStringContainsString('ac-sdg-count', $four);
        $this->assertStringNotContainsString('ac-sdg-aim', $four);
    }

    public function test_report_counts_published_stories_and_projects_per_goal(): void
    {
        $this->data();
        $this->assertSame([6], News::where('slug', 'water')->value('sdgs'));

        $this->get(route('sdgs.index'))->assertOk()
            ->assertSee('Our work for the Sustainable Development Goals')
            ->assertSee(route('sdgs.show', 17))
            ->assertSee('College of Engineering')
            ->assertDontSee('Hidden College')
            ->assertViewHas('stats', ['goals' => 2, 'items' => 2, 'colleges' => 1])
            ->assertViewHas('counts', [6 => 2, 11 => 1]);
    }

    public function test_goal_page_shows_aim_colleges_projects_and_stories(): void
    {
        $this->data();

        $this->get(route('sdgs.show', 6))->assertOk()
            ->assertSee('Clean Water and Sanitation')
            ->assertSee('Ensure availability and sustainable management of water')
            ->assertSee('Barangay water survey')
            ->assertSee('Clean water for Dubinan')
            ->assertDontSee('Draft water story')
            ->assertDontSee('Secret project')
            ->assertSee(route('sdgs.show', 5))
            ->assertSee(route('sdgs.show', 7));

        // The news filter finds the same story.
        $this->get(route('news.index', ['sdg' => 6]))->assertSee('Clean water for Dubinan');

        $this->get(route('sdgs.show', 2))->assertOk()->assertSee('No stories or projects are tagged with this goal yet.');
        $this->get('/sdgs/18')->assertNotFound();
    }

    public function test_goal_texts_come_from_the_admin(): void
    {
        $this->data();
        Term::where(['type' => 'sdg', 'key' => '6'])->first()->update(['label' => 'Water for All', 'description' => 'Our own aim text.']);

        $this->get(route('sdgs.show', 6))->assertSee('Water for All')->assertSee('Our own aim text.')->assertDontSee('Clean Water and Sanitation');
    }
}
