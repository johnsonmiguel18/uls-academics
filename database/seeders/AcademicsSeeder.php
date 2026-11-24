<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\FacultyMember;
use App\Models\Program;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Builds the Academics Portal from the content ULS already publishes
 * (database/seeders/data/uls-pages.json). Safe to re-run: records are matched by slug.
 * Deans then complete their college in the admin panel (overview, vision, faculty...).
 */
class AcademicsSeeder extends Seeder
{
    /** page slug (also the portal slug) => [photo in public/media/photos, seal in public/media/colleges/seals] */
    private const COLLEGES = [
        'college-of-nursing-public-health-and-midwifery' => ['nursing.jpg', 'nursing.webp'],
        'college-of-information-technology' => ['it.jpg', 'information-technology.webp'],
        'college-of-medicine-and-allied-medical-programs' => ['medlab.jpg', 'medical-allied.webp'],
        'college-of-law' => ['law.jpg', null],
        'college-of-business-education' => ['business.jpg', 'business-education.webp'],
        'college-of-accountancy' => ['accounting.jpg', 'accountancy.webp'],
        'college-of-engineering-and-architecture' => ['engineering.jpg', 'engineering-architecture.webp'],
        'college-of-criminal-justice-education' => ['criminology.jpg', 'criminal-justice.webp'],
        'college-of-arts-and-sciences' => ['science.jpg', 'arts-science.webp'],
        'college-of-teacher-education' => ['teacher.jpg', 'teachers-education.webp'],
    ];

    private array $pages;

    public function run(): void
    {
        $this->pages = collect(json_decode(file_get_contents(database_path('seeders/data/uls-pages.json')), true))
            ->keyBy('slug')
            ->all();

        // Seeding is not editor activity: keep it out of the audit log.
        Model::withoutEvents(function (): void {
            $offerings = $this->offerings();
            $order = 0;

            foreach (self::COLLEGES as $pageSlug => [$photo, $seal]) {
                $page = $this->pages[$pageSlug];
                $this->seedCollege($page['title'], $pageSlug, 'undergraduate', $page['content'], $offerings[$page['title']] ?? [], $photo, $seal, ++$order);
            }

            $this->seedCollege('Graduate School', 'graduate-school', 'graduate', $this->pages['graduate-programs']['content'], $offerings['Graduate School'], 'graduation.jpg', null, ++$order);
            $this->seedBasicEducation(++$order);
            $this->seedEteeap();
        });
    }

    /** Program names per college, from the "Academic Programs Offered" page. */
    private function offerings(): array
    {
        $result = [];

        foreach ($this->pages['academic-programs']['content'] as $block) {
            $heading = $block['data']['heading'];
            $college = $heading === 'Graduate School' ? 'Graduate School' : null;

            foreach (preg_split('/\R/', $block['data']['body']) as $line) {
                if (str_starts_with($line, '### ')) {
                    $college = trim(substr($line, 4));
                } elseif ($college && str_starts_with($line, '- ')) {
                    $result[$college][] = trim(substr($line, 2));
                }
            }
        }

        return $result;
    }

    private function seedCollege(string $name, string $slug, string $level, array $blocks, array $programNames, string $photo, ?string $seal, int $order): void
    {
        $college = College::updateOrCreate(['slug' => $slug], [
            'name' => $name,
            'level' => $level,
            'status' => 'published',
            'sort_order' => $order,
            'banner_image' => $this->copyMedia("photos/$photo"),
            'seal_image' => $seal ? $this->copyMedia("colleges/seals/$seal") : null,
        ]);

        $programs = [];
        foreach ($programNames as $i => $line) {
            [$programName, $majors] = array_pad(array_map('trim', explode('—', $line, 2)), 2, null);
            $programs[] = Program::updateOrCreate(['slug' => Str::slug($programName)], [
                'college_id' => $college->id,
                'name' => $programName,
                'majors' => $majors ? preg_replace('/^Major in:?\s*/', '', $majors) : null,
                'level' => $level,
                'degree_type' => $this->degreeType($programName),
                'status' => 'published',
                'is_featured' => $i === 0,
                'sort_order' => $i,
                'image' => $college->banner_image,
                'admission_requirements' => $this->admissionRequirements($level),
            ]);
        }

        $goals = [];
        $details = array_fill_keys(array_keys($programs), []);

        foreach ($blocks as $block) {
            $heading = trim($block['data']['heading'] ?? '');
            $body = trim($block['data']['body'] ?? '');

            match (true) {
                in_array($heading, ['College Leadership', 'Leadership']) => $this->seedLeaders($college, $body),
                str_starts_with($heading, 'Program Offering') => null,
                str_contains($heading, 'Vision') => $college->vision = $body,
                str_contains($heading, 'Mission') || str_contains($heading, 'Mision') => $college->mission = $body,
                $heading === 'Programs in Detail' => $this->splitDetails($body, $programs, $details, $goals),
                // One-program colleges (Law, Criminology): program goals belong to that program.
                count($programs) === 1 && str_contains($heading, 'Program') => $details[0][] = "### $heading\n\n$body",
                default => $goals[] = "### $heading\n\n$body",
            };
        }

        $college->goals = implode("\n\n", $goals) ?: null;
        $college->save();

        foreach ($programs as $i => $program) {
            $program->update(['overview' => implode("\n\n", $details[$i]) ?: null]);
        }
    }

    /** "Programs in Detail": each "### <program name>" starts that program; sub-headings stay with it. */
    private function splitDetails(string $body, array $programs, array &$details, array &$goals): void
    {
        $normalize = fn (string $s): string => Str::of($s)->replace(['**', '(Bachelor of Laws)'], '')->replaceEnd(' Program', '')->squish()->lower()->value();
        $current = null;

        foreach (preg_split('/^### /m', $body) as $index => $chunk) {
            // Text before the first "###" has no heading of its own.
            [$heading, $text] = $index === 0 ? ['', $chunk] : array_pad(explode("\n", $chunk, 2), 2, '');
            $heading = trim($heading);

            foreach ($programs as $i => $program) {
                $name = $normalize($program->name);
                if ($heading !== '' && str_starts_with($normalize($heading), $name)) {
                    $current = $i;
                    // Exact program title: its text is the overview; a longer one ("... Major in English") keeps its heading.
                    $text = $normalize($heading) === $name ? $text : "### $heading\n$text";
                    $heading = null;
                    break;
                }
            }

            $section = trim($heading ? "### $heading\n$text" : $text);
            if ($section === '') {
                continue;
            }

            if ($current === null) {
                $goals[] = $section;
            } else {
                $details[$current][] = $section;
            }
        }
    }

    private function seedLeaders(College $college, string $body): void
    {
        preg_match_all('/\*\*(.+?):\*\*\s*(.+)/', $body, $matches, PREG_SET_ORDER);

        foreach ($matches as $i => [, $title, $name]) {
            $name = trim($name);
            if ($i === 0) {
                $college->dean_name = $name;
                $college->dean_title = "$title, {$college->name}";
            }

            FacultyMember::updateOrCreate(
                ['college_id' => $college->id, 'name' => $name],
                ['position' => trim($title), 'sort_order' => $i],
            );
        }
    }

    private function seedBasicEducation(int $order): void
    {
        $intro = collect($this->pages['basic-education']['content'])->pluck('data.body', 'data.heading');

        $college = College::updateOrCreate(['slug' => 'basic-education'], [
            'name' => 'Basic Education',
            'level' => 'basic',
            'status' => 'published',
            'sort_order' => $order,
            'overview' => $intro['La Salette Philosophy of Education'] ?? null,
            'vision' => $intro['Vision'] ?? null,
            'banner_image' => $this->copyMedia('photos/campus.jpg'),
        ]);

        foreach (['grade--school' => 'Grade School', 'high-school' => 'High School'] as $i => $name) {
            $sections = collect($this->pages[$i]['content'])
                ->reject(fn ($b) => in_array($b['data']['heading'], ['La Salette Philosophy of Education', 'Vision']))
                ->map(fn ($b) => "### {$b['data']['heading']}\n\n{$b['data']['body']}")
                ->implode("\n\n");

            Program::updateOrCreate(['slug' => Str::slug($name)], [
                'college_id' => $college->id,
                'name' => $name,
                'level' => 'basic',
                'degree_type' => 'basic',
                'status' => 'published',
                'sort_order' => $name === 'High School' ? 1 : 0,
                'image' => $college->banner_image,
                'overview' => $sections ?: null,
            ]);
        }
    }

    /**
     * ETEEAP tracks, in the ETEEAP unit (EteeapUnitSeeder). The source page lists them without a college;
     * the matching college still gives each track its photo.
     */
    private function seedEteeap(): void
    {
        $colleges = [
            'Bachelor of Science in Public Administration' => 'college-of-arts-and-sciences',
            'Bachelor of Science in Business Administration' => 'college-of-business-education',
        ];
        $block = collect($this->pages['academic-programs']['content'])->first(fn ($b) => str_starts_with($b['data']['heading'], 'ETEEAP'));

        foreach (preg_split('/\R/', $block['data']['body']) as $line) {
            [$name, $majors] = array_pad(array_map('trim', explode('—', ltrim($line, '- '), 2)), 2, null);
            $college = College::where('slug', $colleges[$name] ?? null)->first();
            if (! $college) {
                continue;
            }

            Program::updateOrCreate(['slug' => Str::slug("$name eteeap")], [
                'college_id' => EteeapUnitSeeder::unit()->id, // its own unit, like Basic Education (deans, Nov 2026)
                'name' => "$name (ETEEAP)",
                'majors' => $majors ? preg_replace('/^Major in:?\s*/', '', $majors) : null,
                'level' => 'undergraduate',
                'degree_type' => 'eteeap',
                'status' => 'published',
                'sort_order' => 90,
                'image' => $college->banner_image,
                'summary' => 'Expanded Tertiary Education Equivalency and Accreditation Program: a degree for working adults that credits prior learning and work experience.',
            ]);
        }
    }

    private function degreeType(string $name): string
    {
        return match (true) {
            str_starts_with($name, 'Juris Doctor') => 'juris-doctor',
            str_starts_with($name, 'Doctor') => 'doctorate',
            str_starts_with($name, 'Master') => 'master',
            default => 'bachelor',
        };
    }

    private function admissionRequirements(string $level): ?string
    {
        $sections = collect($this->pages['admission-requirements']['content'])->pluck('data.body', 'data.heading');
        $wanted = $level === 'graduate'
            ? ['For Graduate School']
            : ['For New Students / High School Graduates', 'For Transferees', 'For Second Coursers'];

        return collect($wanted)
            ->filter(fn ($h) => isset($sections[$h]))
            ->map(fn ($h) => "### $h\n\n{$sections[$h]}")
            ->implode("\n\n") ?: null;
    }

    /** Copy a bundled photo to the public disk, where admin uploads also live. */
    private function copyMedia(string $path): ?string
    {
        $source = public_path("media/$path");
        if (! is_file($source)) {
            return null;
        }

        $target = 'academics/'.str_replace('/', '-', $path);
        Storage::disk('public')->put($target, file_get_contents($source));

        return $target;
    }
}
