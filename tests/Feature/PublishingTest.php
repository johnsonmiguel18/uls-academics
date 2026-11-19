<?php

namespace Tests\Feature;

use App\Filament\Pages\PublishingTimeline;
use App\Models\College;
use App\Models\News;
use App\Models\User;
use App\Support\Prose;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublishingTest extends TestCase
{
    use RefreshDatabase;

    private function story(array $attributes = []): News
    {
        static $n = 0;
        $n++;

        return News::create($attributes + ['title' => "Story $n", 'slug' => "story-$n", 'category' => 'college-news', 'content' => '<p>x</p>', 'status' => 'published']);
    }

    public function test_status_follows_the_publish_date(): void
    {
        $now = $this->story(['status' => 'published']);
        $later = $this->story(['status' => 'published', 'published_at' => now()->addDay()]);
        $overdue = $this->story(['status' => 'scheduled', 'published_at' => now()->subMinute()]);

        $this->assertNotNull($now->published_at);          // "Publish now" gets today's date
        $this->assertSame('scheduled', $later->status);   // a future date means scheduled
        $this->assertSame('published', $overdue->status);
        $this->assertSame('live', $now->state());
    }

    public function test_site_shows_stories_only_between_publish_and_take_down_dates(): void
    {
        $live = $this->story(['title' => 'Live story']);
        $this->story(['title' => 'Future story', 'published_at' => now()->addDays(2)]);
        $this->story(['title' => 'Expired story', 'published_at' => now()->subDays(5), 'unpublish_at' => now()->subDay()]);
        $this->story(['title' => 'Review story', 'status' => 'review']);

        $this->assertSame([$live->id], News::published()->pluck('id')->all());
        $this->get(route('news.index'))->assertOk()->assertSee('Live story')->assertDontSee('Future story')->assertDontSee('Expired story')->assertDontSee('Review story');
    }

    public function test_scheduler_marks_due_stories_published(): void
    {
        $story = $this->story(['published_at' => now()->addHour()]);
        $this->travel(2)->hours();

        $this->artisan('news:publish-scheduled')->assertSuccessful();

        $this->assertSame('published', $story->fresh()->status);
    }

    public function test_timeline_shows_bars_and_reschedules_by_dragging(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $story = $this->story(['title' => 'Enrollment reminder', 'published_at' => now()->addDays(3)->setTime(9, 0)]);

        $this->actingAs($admin)->get(PublishingTimeline::getUrl())->assertOk()->assertSee('Enrollment reminder')->assertSee('Scheduled');

        Livewire::test(PublishingTimeline::class)->call('reschedule', $story->id, 2);

        $this->assertTrue($story->fresh()->published_at->equalTo(now()->addDays(5)->setTime(9, 0)));
    }

    public function test_college_admin_cannot_reschedule_another_colleges_story(): void
    {
        $own = College::create(['name' => 'A', 'slug' => 'a', 'status' => 'published']);
        $other = College::create(['name' => 'B', 'slug' => 'b', 'status' => 'published']);
        $story = $this->story(['college_id' => $other->id, 'published_at' => now()->addDay()]);
        $user = User::factory()->create(['college_id' => $own->id]);
        $user->assignRole('college_admin');
        $before = $story->published_at;

        $this->actingAs($user);
        Livewire::test(PublishingTimeline::class)->call('reschedule', $story->id, 3);

        $this->assertTrue($story->fresh()->published_at->equalTo($before));
    }

    public function test_old_markdown_and_new_rich_text_both_render(): void
    {
        $this->assertTrue(Prose::isMarkdown("- one\n- two"));
        $this->assertFalse(Prose::isMarkdown('<p>Hello</p>'));
        $this->assertStringContainsString('<li>one</li>', Prose::html("- one\n- two"));
        $this->assertStringContainsString('<mark>', Prose::html('<p>A <mark>highlight</mark></p>'));
        $this->assertStringNotContainsString('<script', Prose::html('<p>x</p><script>alert(1)</script>'));
    }
}
