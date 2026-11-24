<?php

namespace Tests\Feature;

use App\Filament\Resources\PartnershipInquiries\PartnershipInquiryResource;
use App\Models\College;
use App\Models\News;
use App\Models\PartnershipInquiry;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\RouteCollection;
use Tests\TestCase;

/** Global Engagement & Linkages: /global (or its own subdomain), partners, stories, inquiries. */
class GlobalEngagementTest extends TestCase
{
    use RefreshDatabase;

    private function setting(string $key, mixed $value): void
    {
        $setting = SiteSetting::first();
        $data = $setting->data;
        data_set($data, $key, $value);
        $setting->update(['data' => $data]);
    }

    private function content(): void
    {
        College::create([
            'name' => 'College of Engineering', 'slug' => 'coe', 'status' => 'published', 'sort_order' => 1,
            'partners' => [
                ['name' => 'DPWH Isabela', 'type' => 'Government partner', 'scope' => 'local'],
                ['name' => 'Kyoto Partner University', 'scope' => 'international', 'country' => 'Japan'],
                ['name' => 'Old partner without reach'],
            ],
        ]);
        College::create(['name' => 'Draft College', 'slug' => 'draft', 'status' => 'draft', 'partners' => [['name' => 'Hidden partner']]]);
        $this->setting('global.partners', [['name' => 'CHED Region II', 'scope' => 'local']]);

        News::create(['title' => 'ULS signs agreement with DICT', 'slug' => 'dict', 'category' => 'linkages', 'status' => 'published', 'published_at' => now()->subDay(), 'content' => 'x']);
        News::create(['title' => 'Exam schedule posted', 'slug' => 'exams', 'category' => 'announcement', 'status' => 'published', 'published_at' => now()->subDay(), 'content' => 'x']);
    }

    public function test_page_lists_college_and_university_partners_and_partnership_stories(): void
    {
        $this->content();

        $this->get(route('global.home'))->assertOk()
            ->assertSee('Global Engagement &amp; Linkages', false)
            ->assertSee('Our shared future.')
            ->assertSee(['DPWH Isabela', 'Kyoto Partner University', 'Japan', 'Old partner without reach', 'CHED Region II'])
            ->assertDontSee('Hidden partner')
            ->assertSee('data-scope="international"', false)
            ->assertSee('data-college="university"', false)
            ->assertViewHas('stats', ['partners' => 4, 'local' => 3, 'international' => 1, 'colleges' => 1])
            ->assertSee('ULS signs agreement with DICT')
            ->assertDontSee('Exam schedule posted')
            ->assertSee('href="#partners"', false)          // menu from settings
            ->assertDontSee('name="_token"', false);        // safe to cache for everyone
    }

    public function test_sections_follow_the_admin(): void
    {
        $this->content();
        $sections = SiteSetting::first()->data['global']['sections'];
        $sections = collect($sections)->map(fn ($s) => $s['type'] === 'faq' ? ['enabled' => false] + $s : $s)
            ->map(fn ($s) => $s['type'] === 'partners' ? ['heading' => 'Who we work with'] + $s : $s)->all();
        $this->setting('global.sections', $sections);

        $this->get(route('global.home'))->assertSee('Who we work with')->assertDontSee('A helpful starting point');
    }

    public function test_inquiries_are_saved_and_shown_to_admins(): void
    {
        $this->post(route('global.inquire'), ['name' => 'Ana Cruz', 'email' => 'ana@example.edu', 'organization' => 'Partner U', 'interest' => 'exchange', 'message' => 'Student exchange in 2027?'])
            ->assertRedirect(route('global.home') . '#connect');
        $this->assertDatabaseHas('partnership_inquiries', ['email' => 'ana@example.edu', 'interest' => 'exchange', 'status' => 'new']);

        // The thank-you page is shown to this visitor only, never cached.
        $this->get(route('global.home'))->assertSee('Your inquiry was sent');
        $this->get(route('global.home'))->assertDontSee('Your inquiry was sent');

        // Invalid input and spam are not saved.
        $this->post(route('global.inquire'), ['name' => '', 'email' => 'nope', 'message' => '', 'interest' => 'hacking'])
            ->assertSessionHasErrors(['name', 'email', 'message', 'interest']);
        $this->post(route('global.inquire'), ['name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'Buy', 'website' => 'spam.example']);
        $this->assertSame(1, PartnershipInquiry::count());

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->get(PartnershipInquiryResource::getUrl())->assertOk()->assertSee('Ana Cruz');

        $dean = User::factory()->create();
        $dean->assignRole('college_admin');
        $this->actingAs($dean)->get(PartnershipInquiryResource::getUrl())->assertForbidden();
    }

    public function test_the_old_subdomain_redirects_to_the_academics_home(): void
    {
        config(['app.global_domain' => 'global.uls.test', 'app.url' => 'http://academics.uls.test']);
        app('router')->setRoutes(new RouteCollection);
        \Illuminate\Support\Facades\Route::middleware('web')->group(base_path('routes/web.php'));
        app('router')->getRoutes()->refreshNameLookups();

        // The old Global subdomain sends every address to the Academics home (deans' decision).
        $this->get('http://global.uls.test/')->assertStatus(301)->assertRedirect('http://academics.uls.test/');
        $this->get('http://global.uls.test/partners')->assertStatus(301)->assertRedirect('http://academics.uls.test/');
        $this->get('http://academics.uls.test/')->assertOk()->assertSee('Academic Excellence at ULS');
        // The Global page lives on the Academics host, with its links there too.
        $this->get('http://academics.uls.test/global')->assertOk()->assertSee('Our shared future.');
        $this->assertSame('http://academics.uls.test/global', route('global.home'));
        $this->assertSame('http://academics.uls.test/sdgs/17', route('sdgs.show', 17));
    }

    public function test_partners_from_the_global_office_list_are_added_once_with_logos(): void
    {
        $this->seed(\Database\Seeders\SiteContentSeeder::class);
        // A partner an admin already entered, with their own wording, under one of the same names.
        $setting = \App\Models\SiteSetting::query()->first();
        $data = $setting->data;
        $data['global']['partners'] = [['name' => 'Panpacific University', 'type' => 'Edited in the admin', 'scope' => 'local']];
        $setting->update(['data' => $data]);

        try {
            $this->seed(\Database\Seeders\GlobalPartnersSeeder::class);
            $this->seed(\Database\Seeders\GlobalPartnersSeeder::class); // second run adds nothing

            $partners = collect(\App\Support\Site::get('global.partners'));
            $this->assertCount(12, $partners);
            $this->assertSame('Edited in the admin', $partners->firstWhere('name', 'Panpacific University')['type']);
            $this->assertSame('international', $partners->firstWhere('name', 'Universidade da Paz (UNPAZ)')['scope']);
            $this->assertFileExists(storage_path('app/public/partners/unpaz.jpg'));

            $this->get(route('global.home'))->assertOk()
                ->assertSee('National Chi Nan University (NCNU)')
                ->assertSee('storage/partners/ncnu.png', false)
                ->assertSee('Philippine National Red Cross, Santiago City Chapter');
        } finally {
            \Illuminate\Support\Facades\File::deleteDirectory(storage_path('app/public/partners'));
        }
    }
}
