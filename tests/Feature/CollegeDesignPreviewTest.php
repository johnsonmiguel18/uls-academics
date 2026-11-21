<?php

namespace Tests\Feature;

use App\Models\AcademicEvent;
use App\Models\College;
use App\Models\Department;
use App\Models\Document;
use App\Models\FacultyMember;
use App\Models\Program;
use Database\Seeders\CollegeDesignPreviewSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Sample content for the deans' design review: fills only empty fields, safe to run twice, removable without touching real content. */
class CollegeDesignPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->artisan('academics:remove-design-preview');
        parent::tearDown();
    }

    public function test_samples_fill_every_section_and_remove_cleanly_around_real_content(): void
    {
        $this->seed(\Database\Seeders\SiteContentSeeder::class);
        $empty = College::create(['name' => 'College of Testing', 'slug' => 'college-of-testing', 'status' => 'published']);
        $real = College::create(['name' => 'College of Real Content', 'slug' => 'college-of-real-content', 'status' => 'published', 'vision' => 'Real vision']);
        $program = Program::create(['college_id' => $empty->id, 'name' => 'BS Testing', 'slug' => 'bs-testing', 'degree_type' => 'bachelor', 'status' => 'published', 'overview' => 'Real overview']);
        $dean = FacultyMember::create(['college_id' => $empty->id, 'name' => 'Real Dean, PhD', 'position' => 'Dean']);

        $this->seed(CollegeDesignPreviewSeeder::class);
        $this->seed(CollegeDesignPreviewSeeder::class);

        // Every section of the college page has something to show.
        $this->get(route('academics.college', $empty))->assertOk()->assertSeeInOrder([
            '[SAMPLE] One line on what the college is known for',
            '[SAMPLE] Full name of the research cluster',
            '[SAMPLE] Name of a partner school',
            '[SAMPLE] Internships',
            '[SAMPLE] Name of an extension project',
            'Sample Faculty Name, PhD',
            '[SAMPLE] The rooms and equipment students use',
            '[SAMPLE] Curriculum guide',
            '[SAMPLE] Research colloquium',
        ], false);
        $this->get(route('academics.department', [$empty, Department::where('college_id', $empty->id)->first()]))->assertOk()->assertSee('[SAMPLE] A recent achievement', false);
        $this->get(route('academics.program', $program))->assertOk()->assertSee('Real overview')->assertSee('[SAMPLE] Can I shift into this program?', false);

        // Run twice, nothing doubled; real content untouched.
        $this->assertSame(2, Department::where('college_id', $empty->id)->count());
        $this->assertSame(2, AcademicEvent::where('college_id', $empty->id)->count());
        $this->assertSame(4, FacultyMember::where('college_id', $empty->id)->count());
        $this->assertSame('Real vision', $real->fresh()->vision);
        $this->assertSame('Real overview', $program->fresh()->overview);

        // The dean types over a sample and adds a real partner next to the sample ones.
        $real->update(['tagline' => 'Real tagline', 'partners' => [...$real->fresh()->partners, ['name' => 'Real Partner University']]]);

        $this->artisan('academics:remove-design-preview')->expectsOutputToContain('sample records')->assertSuccessful();

        $real->refresh();
        $this->assertSame('Real vision', $real->vision);
        $this->assertSame('Real tagline', $real->tagline);
        $this->assertSame(['Real Partner University'], array_column($real->partners, 'name'));
        $this->assertNull($real->headline);
        $this->assertNull($real->research_highlights);
        $this->assertNull($empty->fresh()->email);
        $this->assertSame('Real overview', $program->fresh()->overview);
        $this->assertNull($program->fresh()->faqs);
        $this->assertSame(0, Department::count() + AcademicEvent::count() + Document::count());
        $this->assertSame([$dean->id], FacultyMember::pluck('id')->all());
        $this->assertDirectoryDoesNotExist(storage_path('app/public/design-preview-colleges'));
    }
}
