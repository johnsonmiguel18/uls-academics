<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\Department;
use App\Models\FacultyMember;
use App\Support\FacultyTree;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Faculty shown as an organization chart by default, built from positions and departments already entered. */
class FacultyTreeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SiteContentSeeder::class);
    }

    private function person(College $college, string $name, ?string $position, ?Department $department = null, array $extra = []): FacultyMember
    {
        return FacultyMember::create($extra + ['college_id' => $college->id, 'department_id' => $department?->id, 'name' => $name, 'position' => $position]);
    }

    public function test_people_are_placed_by_position_and_department(): void
    {
        $college = College::create(['name' => 'College of Engineering and Architecture', 'short_name' => 'CEA', 'slug' => 'cea', 'status' => 'published']);
        $civil = Department::create(['college_id' => $college->id, 'name' => 'Civil Engineering', 'slug' => 'civil']);
        $this->person($college, 'Dr. Paolo Navarro', 'Dean');
        $this->person($college, 'Engr. Ana Cruz', 'Assistant Dean');
        $this->person($college, 'Engr. Ben Reyes', 'Department Chair', $civil);
        $this->person($college, 'Engr. Carla Santos', 'Instructor', $civil);
        $this->person($college, 'Arch. Dan Lim', 'Faculty');
        $this->person($college, 'Hidden Person', 'Faculty', null, ['is_published' => false]);

        $tree = FacultyTree::for($college->load('faculty', 'departments'));

        $this->assertSame('Dr. Paolo Navarro', $tree['root']->name);
        $this->assertSame(['office', 'department', 'faculty'], array_column($tree['units'], 'type'));
        $this->assertSame(['Engr. Ana Cruz'], $tree['units'][0]['members']->pluck('name')->all());
        $this->assertSame('Engr. Ben Reyes', $tree['units'][1]['lead']->name);
        $this->assertSame(['Engr. Carla Santos'], $tree['units'][1]['members']->pluck('name')->all());
        $this->assertSame(['Arch. Dan Lim'], $tree['units'][2]['members']->pluck('name')->all());

        $html = $this->get(route('academics.college', $college))->assertOk()->getContent();
        $this->assertStringContainsString('class="ac-org"', $html);
        $this->assertStringContainsString('aria-label="CEA organization"', $html);
        $this->assertStringContainsString('Office of the Dean', $html);
        $this->assertStringContainsString(route('academics.department', [$college, $civil]), $html);
        $this->assertStringNotContainsString('Hidden Person', $html);
    }

    public function test_the_dean_comes_from_the_college_when_there_is_no_dean_profile(): void
    {
        $college = College::create(['name' => 'College of Law', 'slug' => 'law', 'status' => 'published',
            'dean_name' => 'Atty. Josefina Dizon', 'dean_title' => 'Dean, College of Law']);
        $this->person($college, 'Atty. Mark Uy', 'Professor');

        $tree = FacultyTree::for($college->load('faculty', 'departments'));

        $this->assertSame('Atty. Josefina Dizon', $tree['root']->name);
        $this->assertFalse($tree['root']->exists, 'built from the college, not saved');
        $this->assertSame(['Atty. Mark Uy'], $tree['units'][0]['members']->pluck('name')->all());
    }
}
