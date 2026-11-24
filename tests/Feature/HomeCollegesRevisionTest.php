<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\Program;
use App\Models\SiteSetting;
use App\Support\Sdg;
use Database\Seeders\CollegeColorsSeeder;
use Database\Seeders\EteeapUnitSeeder;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Deans' recommendations (November 2026): college colors, ETEEAP as its own unit, hero photo, card placeholders. */
class HomeCollegesRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SiteContentSeeder::class);
    }

    public function test_college_colors_are_seeded_once_and_keep_admin_choices(): void
    {
        $nursing = College::create(['name' => 'College of Nursing', 'slug' => 'college-of-nursing-public-health-and-midwifery', 'status' => 'published']);
        $law = College::create(['name' => 'College of Law', 'slug' => 'college-of-law', 'status' => 'published', 'color' => '#123456']);

        $this->seed(CollegeColorsSeeder::class);

        $this->assertSame('#BE185D', $nursing->fresh()->color);
        $this->assertSame('#123456', $law->fresh()->color, 'a color chosen in the admin stays');
    }

    public function test_the_college_name_color_always_reads_on_white(): void
    {
        foreach ([...array_values(CollegeColorsSeeder::COLORS), '#FFFF00', '#FFFFFF', null] as $color) {
            $college = new College(['color' => $color]);
            $this->assertGreaterThanOrEqual(4.5, Sdg::contrast($college->accentInk(), '#FFFFFF'), (string) $color);
        }
        $this->assertSame('#192A8A', (new College(['color' => 'red']))->accent(), 'invalid colors fall back to royal blue');
        $this->assertSame('CL', (new College(['name' => 'College of Law']))->monogram());
        $this->assertSame('ETEEAP', (new College(['name' => 'Anything', 'short_name' => 'ETEEAP']))->monogram());
    }

    public function test_eteeap_becomes_its_own_unit_with_its_programs_published(): void
    {
        $cas = College::create(['name' => 'College of Arts and Sciences', 'slug' => 'college-of-arts-and-sciences', 'status' => 'published']);
        Program::create(['name' => 'BS Public Administration (ETEEAP)', 'slug' => 'bspa-eteeap', 'college_id' => $cas->id,
            'level' => 'undergraduate', 'degree_type' => 'eteeap', 'status' => 'draft']);
        $other = Program::create(['name' => 'Unrelated draft', 'slug' => 'draft', 'college_id' => $cas->id,
            'level' => 'undergraduate', 'degree_type' => 'bachelor', 'status' => 'draft']);

        $this->seed(EteeapUnitSeeder::class);
        $this->seed(EteeapUnitSeeder::class); // safe to run again

        $unit = College::where('slug', 'eteeap')->sole();
        $this->assertSame('published', $unit->status);
        $this->assertSame(['bspa-eteeap'], $unit->programs()->pluck('slug')->all());
        $this->assertSame('published', Program::where('slug', 'bspa-eteeap')->value('status'));
        $this->assertSame('draft', $other->fresh()->status);

        $this->get(route('academics.index'))->assertOk()
            ->assertSee('Expanded Tertiary Education Equivalency and Accreditation Program')
            ->assertSee(route('academics.college', $unit), false);
    }

    public function test_home_cards_use_the_college_color_and_a_placeholder_without_photos(): void
    {
        $college = College::create(['name' => 'College of Law', 'slug' => 'college-of-law', 'status' => 'published',
            'color' => '#6B21A8', 'banner_image' => 'colleges/law.jpg']);
        Program::create(['name' => 'Juris Doctor', 'slug' => 'jd', 'college_id' => $college->id, 'level' => 'graduate',
            'degree_type' => 'juris-doctor', 'status' => 'published', 'is_featured' => true, 'image' => 'colleges/law.jpg']);

        $html = $this->get(route('academics.index'))->assertOk()->getContent();

        $this->assertStringContainsString('--college: #6B21A8', $html);
        $this->assertStringContainsString('class="ac-monogram">CL<', $html, 'no seal: initials');
        // The program only repeats its college's banner, so its card shows the placeholder, not the same photo.
        $this->assertSame(null, Program::where('slug', 'jd')->first()->ownImage());
        $this->get(route('academics.programs'))->assertSee('ac-program-media is-placeholder', false);
    }

    public function test_the_hero_shows_the_photo_from_site_settings_or_a_placeholder(): void
    {
        $this->get(route('academics.index'))->assertSee('ac-hero-photo is-placeholder', false);

        $setting = SiteSetting::query()->first();
        $data = $setting->data;
        $data['home']['hero_image'] = 'site/campus.jpg';
        $data['home']['hero_image_alt'] = 'Students on the university grounds';
        $setting->update(['data' => $data]);

        $this->get(route('academics.index'))->assertOk()
            ->assertSee('thumbs/960/storage/site/campus.jpg.webp', false)
            ->assertSee('alt="Students on the university grounds"', false)
            ->assertDontSee('ac-hero-photo is-placeholder', false);
    }
}
