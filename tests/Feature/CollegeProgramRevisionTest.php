<?php

namespace Tests\Feature;

use App\Models\Accreditation;
use App\Models\College;
use App\Models\Program;
use App\Models\SiteSetting;
use App\Support\Site;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Deans' recommendations (November 2026) for college and program pages. */
class CollegeProgramRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SiteContentSeeder::class);
    }

    private function college(array $extra = []): College
    {
        return College::create($extra + ['name' => 'College of Information Technology', 'slug' => 'cit', 'status' => 'published', 'color' => '#0E7490']);
    }

    public function test_program_pages_link_to_the_official_admission_page_instead_of_copying_requirements(): void
    {
        $program = Program::create(['name' => 'BS Information Technology', 'slug' => 'bsit', 'college_id' => $this->college()->id,
            'level' => 'undergraduate', 'degree_type' => 'bachelor', 'status' => 'published', 'admission_requirements' => 'Old copied list']);

        $this->get(route('academics.program', $program))->assertOk()
            ->assertSee('href="'.Site::text('contact.admission_url').'"', false)
            ->assertSee('See admission requirements')
            ->assertDontSee('Old copied list');
    }

    public function test_the_accreditation_section_lists_accredited_programs_and_university_rankings(): void
    {
        $college = $this->college();
        $accredited = Program::create(['name' => 'BS Information Technology', 'slug' => 'bsit', 'college_id' => $college->id,
            'level' => 'undergraduate', 'degree_type' => 'bachelor', 'status' => 'published']);
        Program::create(['name' => 'BS Computer Science', 'slug' => 'bscs', 'college_id' => $college->id,
            'level' => 'undergraduate', 'degree_type' => 'bachelor', 'status' => 'published']);
        Accreditation::create(['program_id' => $accredited->id, 'agency' => 'PACUCOA', 'level' => 'Level II Accredited', 'valid_until' => '2028-06-30']);

        $setting = SiteSetting::query()->first();
        $data = $setting->data;
        $data['recognitions']['items'][2]['result'] = 'Top 300 in Ethical Values';
        $data['recognitions']['items'][2]['url'] = 'https://example.org/wuri';
        $setting->update(['data' => $data]);

        $html = $this->get(route('academics.college', $college))->assertOk()->getContent();
        $section = substr($html, strpos($html, 'id="accreditation"'));
        $this->assertStringContainsString('PACUCOA Level II Accredited · Valid until June 2028', $section);
        $this->assertStringContainsString(route('academics.program', $accredited), $section);
        $this->assertStringNotContainsString(route('academics.program', 'bscs'), substr($section, 0, strpos($section, '</section>')));
        $this->assertStringContainsString('Top 300 in Ethical Values', $section);
        $this->assertStringContainsString('About WURI', $section);

        // The same rankings on the home page; entries without a link show no link.
        $this->get(route('academics.index'))->assertOk()
            ->assertSee('id="recognitions"', false)
            ->assertSee('Top 300 in Ethical Values')
            ->assertDontSee('About PACUCOA');
    }

    public function test_existing_settings_are_updated_once_and_keep_edited_wording(): void
    {
        $setting = SiteSetting::query()->first();
        $data = $setting->data;
        $data['college']['sections'] = [
            ['type' => 'about', 'enabled' => true, 'nav_label' => 'About'],
            ['type' => 'partners', 'enabled' => true, 'nav_label' => 'Partners'],
            ['type' => 'sdgs', 'enabled' => false, 'nav_label' => 'Extension & SDGs', 'kicker' => 'Our own kicker', 'heading' => 'Edited heading'],
        ];
        $data['college']['stats'] = [['metric' => 'sdgs', 'label' => 'SDGs', 'enabled' => true], ['metric' => 'programs', 'label' => 'Programs', 'enabled' => true]];
        $data['home']['sections'] = [['type' => 'featured', 'enabled' => true], ['type' => 'news', 'enabled' => true]];
        $setting->update(['data' => $data]);

        $migration = require database_path('migrations/2026_11_24_110000_update_page_sections_for_deans_revisions.php');
        $migration->up();
        $migration->up(); // a second run changes nothing

        $data = SiteSetting::query()->first()->data;
        $this->assertSame(['about', 'partners', 'accreditation', 'extension'], array_column($data['college']['sections'], 'type'));
        $extension = $data['college']['sections'][3];
        $this->assertSame(['Extension', 'Our own kicker', 'Edited heading', false], [$extension['nav_label'], $extension['kicker'], $extension['heading'], $extension['enabled']]);
        $this->assertSame([false, true], array_column($data['college']['stats'], 'enabled'));
        $this->assertSame(['featured', 'recognitions', 'news'], array_column($data['home']['sections'], 'type'));

        $migration->down();
        $data = SiteSetting::query()->first()->data;
        $this->assertSame(['about', 'partners', 'sdgs'], array_column($data['college']['sections'], 'type'));
        $this->assertSame('Extension & SDGs', $data['college']['sections'][2]['nav_label']);
    }

    public function test_college_pages_show_the_college_color_and_seal_placeholder(): void
    {
        $this->get(route('academics.college', $this->college()))->assertOk()
            ->assertSee('--college: #0E7490', false)
            ->assertSee('--ac-accent: #0E7490', false)
            ->assertSee('class="ac-monogram">CIT<', false);
    }
}
