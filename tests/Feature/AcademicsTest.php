<?php

namespace Tests\Feature;

use App\Filament\Resources\Programs\Pages\CreateProgram;
use App\Filament\Resources\Programs\ProgramResource;
use App\Models\College;
use App\Models\News;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\AcademicsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AcademicsTest extends TestCase
{
    use RefreshDatabase;

    private function college(array $attributes = []): College
    {
        static $n = 0;
        $n++;

        return College::create($attributes + ['name' => "College $n", 'slug' => "college-$n", 'status' => 'published']);
    }

    private function program(College $college, array $attributes = []): Program
    {
        static $n = 0;
        $n++;

        return Program::create($attributes + [
            'college_id' => $college->id,
            'name' => "Program $n",
            'slug' => "program-$n",
            'degree_type' => 'bachelor',
            'status' => 'published',
        ]);
    }

    public function test_public_pages_show_only_published_content(): void
    {
        $college = $this->college(['name' => 'College of Information Technology']);
        $program = $this->program($college, ['name' => 'Bachelor of Science in Information Technology', 'overview' => 'Computing **solutions**.']);
        $draft = $this->program($college, ['name' => 'Secret Draft Program', 'status' => 'draft']);
        $draftCollege = $this->college(['status' => 'draft']);
        $department = $college->departments()->create(['name' => 'IT Department', 'slug' => 'it-department']);

        $this->get(route('academics.index'))->assertOk()->assertSee('College of Information Technology')->assertDontSee($draftCollege->name);
        $this->get(route('academics.college', $college))->assertOk()->assertSee($program->name)->assertDontSee('Secret Draft Program');
        $this->get(route('academics.department', [$college, $department]))->assertOk()->assertSee('IT Department');
        $this->get(route('academics.program', $program))->assertOk()->assertSee('<strong>solutions</strong>', false);

        $this->get(route('academics.program', $draft))->assertNotFound();
        $this->get(route('academics.college', $draftCollege))->assertNotFound();
        // A department is only reachable under its own college.
        $this->get(route('academics.department', [$this->college(), $department]))->assertNotFound();
    }

    public function test_college_page_shows_bcu_style_sections_and_sdg_counts(): void
    {
        $college = $this->college([
            'short_name' => 'CEA',
            'headline' => 'Building engineers for resilient communities',
            'email' => 'cea@example.com',
            'research_cluster' => 'TIBAY',
            'partners' => [['name' => 'Philippine Institute of Civil Engineers', 'type' => 'Professional partner', 'sdgs' => ['4', '9']]],
            'student_benefits' => [['title' => 'Ready for the boards']],
            'extension_projects' => [['title' => 'Water survey', 'sdgs' => ['6', '9']]],
        ]);
        $program = $this->program($college, ['name' => 'Bachelor of Science in Civil Engineering']);
        News::create(['title' => 'Bridge study', 'slug' => 'bridge', 'category' => 'research', 'college_id' => $college->id, 'sdgs' => [9],
            'content' => '<p>x</p>', 'status' => 'published', 'published_at' => now()->subHour()]);
        News::create(['title' => 'Unrelated story', 'slug' => 'other', 'category' => 'college-news',
            'content' => '<p>x</p>', 'status' => 'published', 'published_at' => now()->subHour()]);

        $this->assertSame([4, 6, 9], $college->sdgsSupported());
        $this->assertSame([6 => 1, 9 => 2], $college->sdgStoryCounts());

        $this->get(route('academics.college', $college))->assertOk()
            ->assertSee('Building engineers for resilient communities')
            ->assertSee('What you can study at CEA')
            ->assertSee('mailto:cea@example.com?subject=' . rawurlencode('Inquiry: ' . $program->name), false)
            ->assertSee('TIBAY')
            ->assertSee('Philippine Institute of Civil Engineers')
            ->assertSee('Ready for the boards')
            ->assertSee('Water survey')
            // Goals with stories link to them; goals without stay as plain, lighter tiles.
            ->assertSee(route('news.index', ['college' => $college->slug, 'sdg' => 9]))
            ->assertDontSee(route('news.index', ['college' => $college->slug, 'sdg' => 1]))
            ->assertSee('No Poverty, no stories yet');

        $this->get(route('news.index', ['college' => $college->slug, 'sdg' => 9]))->assertOk()
            ->assertSee('Bridge study')
            ->assertDontSee('Unrelated story');
    }

    public function test_program_finder_filters_and_rejects_bad_input(): void
    {
        $college = $this->college();
        $this->program($college, ['name' => 'Bachelor of Science in Nursing']);
        $this->program($college, ['name' => 'Master of Science in Nursing', 'level' => 'graduate', 'degree_type' => 'master']);
        $this->program($college, ['name' => 'Bachelor of Arts in Philosophy']);

        $this->get(route('academics.programs', ['q' => 'nursing', 'level' => 'graduate']))
            ->assertOk()
            ->assertSee('Master of Science in Nursing')
            ->assertDontSee('Bachelor of Science in Nursing')
            ->assertDontSee('Philosophy');

        $this->get(route('academics.programs', ['level' => 'nonsense']))->assertSessionHasErrors('level');
    }

    public function test_college_page_lists_its_tagged_news(): void
    {
        $college = $this->college();
        News::create(['title' => 'Hackathon win', 'slug' => 'hackathon', 'category' => 'university-news', 'college_id' => $college->id,
            'content' => '<p>x</p>', 'status' => 'published', 'published_at' => now()->subHour()]);

        $this->get(route('academics.college', $college))->assertOk()->assertSee('Hackathon win');
    }

    public function test_college_admin_only_reaches_their_own_college(): void
    {
        $own = $this->college();
        $other = $this->college();
        $ownProgram = $this->program($own);
        $otherProgram = $this->program($other);

        $admin = User::factory()->create(['college_id' => $own->id]);
        $admin->assignRole('college_admin');
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertTrue(ProgramResource::canViewAny());
        $this->assertSame([$ownProgram->id], ProgramResource::getEloquentQuery()->pluck('id')->all());
        $this->assertTrue(ProgramResource::canEdit($ownProgram));
        $this->assertFalse(ProgramResource::canEdit($otherProgram));

        $this->get(ProgramResource::getUrl('edit', ['record' => $otherProgram]))->assertNotFound();

        // Posting another college's id is rejected by the scoped select.
        Livewire::test(CreateProgram::class)
            ->fillForm(['name' => 'Sneaky', 'slug' => 'sneaky', 'college_id' => $other->id, 'level' => 'undergraduate', 'degree_type' => 'bachelor', 'status' => 'draft'])
            ->call('create')
            ->assertHasFormErrors(['college_id']);

        $this->assertDatabaseMissing('programs', ['slug' => 'sneaky']);
    }

    public function test_admin_screens_render_for_managers_and_college_admins(): void
    {
        $college = $this->college();
        $program = $this->program($college);
        $manager = User::factory()->create();
        $manager->assignRole('admin');
        $collegeAdmin = User::factory()->create(['college_id' => $college->id]);
        $collegeAdmin->assignRole('college_admin');

        foreach ([$manager, $collegeAdmin] as $user) {
            $this->actingAs($user);
            foreach (['colleges', 'programs', 'faculty-members', 'academic-events', 'documents', 'news'] as $resource) {
                $this->get("/admin/$resource")->assertOk();
            }
            $this->get("/admin/colleges/{$college->id}/edit")->assertOk();
            $this->get("/admin/programs/{$program->id}/edit")->assertOk();
            $this->get('/admin/programs/create')->assertOk();
        }

        $this->get('/admin/colleges/create')->assertForbidden(); // only managers add colleges
    }

    public function test_college_admin_without_a_college_sees_nothing(): void
    {
        $this->program($this->college());
        $admin = User::factory()->create();
        $admin->assignRole('college_admin');
        $this->actingAs($admin);

        $this->assertFalse(ProgramResource::canViewAny());
        $this->assertSame(0, ProgramResource::getEloquentQuery()->count());
    }

    public function test_seeder_builds_the_portal_from_existing_uls_content(): void
    {
        Storage::fake('public');

        $this->seed(AcademicsSeeder::class);
        $this->seed(AcademicsSeeder::class); // re-runnable

        $this->assertSame(12, College::count());
        $bsit = Program::where('slug', 'bachelor-of-science-in-information-technology')->firstOrFail();
        $this->assertSame('college-of-information-technology', $bsit->college->slug);
        $this->assertStringContainsString('hardware and software', $bsit->overview);
        $this->assertSame('Bienvenido B. Abad, Jr., DIT', $bsit->college->dean_name);
        $this->assertSame('English, Filipino, Mathematics, Sciences, and Social Studies', Program::where('slug', 'bachelor-of-secondary-education')->value('majors'));
        $this->assertSame(0, Program::where('degree_type', 'eteeap')->where('status', 'published')->count());
        Storage::disk('public')->assertExists($bsit->college->banner_image);
    }
}
