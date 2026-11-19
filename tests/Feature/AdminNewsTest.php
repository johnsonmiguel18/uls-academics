<?php

namespace Tests\Feature;

use App\Filament\Resources\News\Pages\CreateNews;
use App\Filament\Resources\News\Pages\EditNews;
use App\Models\News;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Admin portal: articles are created, featured, edited and deleted behind the login. */
class AdminNewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/admin/news')->assertRedirect('/admin/login');
        $this->get('/admin/news/create')->assertRedirect('/admin/login');
    }

    public function test_an_admin_creates_features_edits_and_deletes_an_article(): void
    {
        $this->seed(\Database\Seeders\SiteContentSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Livewire::test(CreateNews::class)
            ->fillForm([
                'title' => 'Solar pump for Barangay Rizal',
                'slug' => 'solar-pump',
                'category' => 'extension',
                'sdgs' => [6, 7],
                'excerpt' => 'Students built a solar water pump.',
                'content' => 'Students built a solar water pump.',
                'status' => 'published',
                'is_featured' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $news = News::where('slug', 'solar-pump')->firstOrFail();
        $this->assertTrue($news->is_featured);
        $this->assertSame([6, 7], $news->sdgs);
        $this->assertSame('published', $news->status);

        // It is the newest featured story, so it opens the news front page.
        $this->get(route('news.index'))->assertOk()->assertViewHas('featuredNews', fn ($hero) => $hero->is($news));

        Livewire::test(EditNews::class, ['record' => $news->getRouteKey()])
            ->fillForm(['title' => 'Solar water pump for Barangay Rizal'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('Solar water pump for Barangay Rizal', $news->fresh()->title);

        Livewire::test(EditNews::class, ['record' => $news->getRouteKey()])
            ->callAction(DeleteAction::class);
        $this->assertModelMissing($news);
    }

    public function test_an_editor_cannot_publish_feature_or_delete(): void
    {
        $this->seed(\Database\Seeders\SiteContentSeeder::class);
        $editor = User::factory()->create();
        $editor->assignRole('editor');
        $this->actingAs($editor);

        Livewire::test(CreateNews::class)
            ->fillForm(['title' => 'Draft story', 'slug' => 'draft-story', 'category' => 'extension', 'content' => 'Text', 'status' => 'draft'])
            ->call('create')
            ->assertHasNoFormErrors();

        $news = News::where('slug', 'draft-story')->firstOrFail();
        $this->assertSame('draft', $news->status);
        $this->assertFalse($news->is_featured);

        Livewire::test(EditNews::class, ['record' => $news->getRouteKey()])->assertActionHidden(DeleteAction::class);
    }
}
