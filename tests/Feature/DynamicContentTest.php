<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings;
use App\Filament\Resources\Terms\Pages\ManageTerms;
use App\Models\College;
use App\Models\News;
use App\Models\Program;
use App\Models\SiteSetting;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Everything the public site shows comes from admin-managed records, and changes show up at once. */
class DynamicContentTest extends TestCase
{
    use RefreshDatabase;

    private function setting(string $key, mixed $value): void
    {
        $setting = SiteSetting::first();
        $data = $setting->data;
        data_set($data, $key, $value);
        $setting->update(['data' => $data]);
    }

    private function college(): College
    {
        $college = College::create(['name' => 'College of Engineering', 'slug' => 'coe', 'short_name' => 'COE', 'status' => 'published', 'vision' => 'Our vision text']);
        Program::create(['college_id' => $college->id, 'name' => 'BS Civil Engineering', 'slug' => 'bsce', 'degree_type' => 'bachelor', 'level' => 'undergraduate', 'status' => 'published']);

        return $college;
    }

    public function test_settings_change_the_site_immediately_even_with_the_page_cache_on(): void
    {
        $this->setting('performance.page_cache_minutes', 10);

        $this->get('/')->assertSee('Academic Excellence at ULS')->assertHeader('X-Page-Cache', 'MISS');
        $this->get('/')->assertHeader('X-Page-Cache', 'HIT');

        $this->setting('home.headline', 'Learn with purpose');
        $this->setting('header.menu', [['label' => 'Scholarships', 'url' => '/news?category=announcement']]);

        $this->get('/')->assertHeader('X-Page-Cache', 'MISS')
            ->assertSee('Learn with purpose')
            ->assertDontSee('Academic Excellence at ULS')
            ->assertSee('Scholarships');
    }

    public function test_only_plain_addresses_on_the_site_host_are_cached(): void
    {
        $this->setting('performance.page_cache_minutes', 10);

        $this->get('/news')->assertHeader('X-Page-Cache', 'MISS');
        $this->get('/news')->assertHeader('X-Page-Cache', 'HIT');

        // Random query strings or Host headers must not each add a cache entry.
        $this->get('/news?utm=1')->assertOk()->assertHeaderMissing('X-Page-Cache');
        $this->get('/news?category=extension')->assertOk()->assertHeaderMissing('X-Page-Cache');
        $this->get('http://attacker.example/news')->assertHeaderMissing('X-Page-Cache');
    }

    public function test_record_changes_refresh_cached_pages(): void
    {
        $this->setting('performance.page_cache_minutes', 10);
        $college = $this->college();

        $this->get(route('academics.college', $college))->assertSee('Our vision text');
        $college->update(['vision' => 'A new vision']);

        $this->get(route('academics.college', $college))->assertSee('A new vision')->assertDontSee('Our vision text');
    }

    public function test_page_cache_expires_when_a_scheduled_story_goes_live(): void
    {
        $this->setting('performance.page_cache_minutes', 60);
        $college = $this->college();
        News::create(['title' => 'Board exam results', 'slug' => 'board', 'category' => 'college-news', 'college_id' => $college->id,
            'content' => '<p>x</p>', 'status' => 'scheduled', 'published_at' => now()->addMinutes(5)]);

        $this->get(route('news.index'))->assertDontSee('Board exam results');
        $this->travel(6)->minutes();
        $this->get(route('news.index'))->assertSee('Board exam results');
    }

    public function test_sections_can_be_reordered_hidden_and_renamed(): void
    {
        $college = $this->college();
        $sections = collect(SiteSetting::first()->data['college']['sections'])->keyBy('type');
        $sections['programs'] = ['heading' => 'Degrees at :short'] + $sections['programs'];
        $sections['about'] = ['enabled' => false] + $sections['about'];
        // Programs first, then the rest.
        $this->setting('college.sections', $sections->sortBy(fn ($s) => $s['type'] === 'programs' ? 0 : 1)->values()->all());

        $html = $this->get(route('academics.college', $college))->assertOk()
            ->assertSee('Degrees at COE')
            ->assertDontSee('Our vision text')
            ->getContent();

        $this->setting('college.sections', $sections->map(fn ($s) => ['enabled' => true] + $s)->values()->all());
        $this->get(route('academics.college', $college))->assertSee('Our vision text');
        $this->assertStringContainsString('Degrees at COE', $html);
    }

    public function test_lists_drive_labels_filters_and_validation(): void
    {
        $this->college();
        Term::where('type', 'degree_type')->where('key', 'bachelor')->first()->update(['label' => 'Baccalaureate']);
        Term::create(['type' => 'level', 'key' => 'senior-high', 'label' => 'Senior High School', 'sort_order' => 1]);

        $this->get(route('academics.programs'))->assertOk()
            ->assertSee('Baccalaureate')                 // renamed label on cards and in the filter
            ->assertSee('Senior High School');           // new level in the filter

        $this->get(route('academics.programs', ['level' => 'senior-high']))->assertOk();
        Term::where('key', 'senior-high')->first()->update(['is_active' => false]);
        $this->get(route('academics.programs', ['level' => 'senior-high']))->assertSessionHasErrors('level');
    }

    public function test_sdg_colors_come_from_the_list(): void
    {
        Term::where('type', 'sdg')->where('key', '4')->first()->update(['color' => '#123456']);

        $this->assertSame('#123456', \App\Support\Sdg::goals()[4][1]);
        $this->assertSame('#FFFFFF', \App\Support\Sdg::goals()[4][2]);   // readable text on it
        [$tint, $ink] = array_slice(\App\Support\Sdg::goals()[4], 3);
        $this->assertGreaterThanOrEqual(4.5, \App\Support\Sdg::contrast($tint, $ink));
    }

    public function test_admins_edit_settings_and_lists_in_the_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $this->get(SiteSettings::getUrl())->assertOk();
        $this->get(\App\Filament\Resources\Terms\TermResource::getUrl())->assertOk();

        Livewire::test(SiteSettings::class)
            ->set('data.home.headline', 'Edited in the admin')
            ->call('save')
            ->assertHasNoErrors();
        $this->get('/')->assertSee('Edited in the admin');

        // Entries in use cannot be deleted (only hidden).
        $this->college();
        $this->assertFalse(\App\Filament\Resources\Terms\TermResource::canDelete(Term::where('key', 'bachelor')->first()));
        $this->assertTrue(\App\Filament\Resources\Terms\TermResource::canDelete(Term::where('key', 'eteeap')->first()));
    }

    public function test_college_admins_cannot_open_site_settings(): void
    {
        $user = User::factory()->create(['college_id' => $this->college()->id]);
        $user->assignRole('college_admin');

        $this->actingAs($user)->get(SiteSettings::getUrl())->assertForbidden();
        $this->assertFalse(\App\Filament\Resources\Terms\TermResource::canViewAny());
    }

    public function test_speed_measurements_are_stored_and_validated(): void
    {
        $this->call('POST', route('vitals'), [], [], [], ['CONTENT_TYPE' => 'text/plain'],
            json_encode(['path' => '/programs?x=1', 'device' => 'mobile', 'metrics' => ['LCP' => 1900, 'CLS' => 0.03, 'HACK' => 1, 'INP' => -5]]))
            ->assertNoContent();

        $this->assertSame(['CLS', 'LCP'], \App\Models\WebVital::orderBy('metric')->pluck('metric')->all());
        $this->assertSame('/programs', \App\Models\WebVital::first()->path);
        $this->assertSame(1900.0, \App\Models\WebVital::p75('LCP', now()->subDay())['value']);
    }
}
