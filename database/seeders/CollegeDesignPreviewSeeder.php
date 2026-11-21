<?php

namespace Database\Seeders;

use App\Models\AcademicEvent;
use App\Models\College;
use App\Models\Department;
use App\Models\Document;
use App\Models\FacultyMember;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Sample content for every section of the college, department and program pages, so the deans can
 * approve the design before the real content is collected.
 *
 *   php artisan db:seed --class=CollegeDesignPreviewSeeder     load (safe to run again)
 *   php artisan academics:remove-design-preview                 remove every sample
 *
 * Only empty fields are filled; whatever a college or program already has stays. Each sample says what
 * belongs there, so it doubles as a guide for the content request. Sample text starts with "[SAMPLE] ";
 * the remover clears a field only while it still holds sample text, and drops only the sample items of a
 * list, so real content typed over a sample is kept. Sample faculty use an @example.org address.
 * Load on a local or staging copy, not on the live site. Not part of DatabaseSeeder on purpose.
 */
class CollegeDesignPreviewSeeder extends Seeder
{
    public const MARK = '[SAMPLE] ';

    public const SLUG_PREFIX = 'sample-';

    public const EMAIL_DOMAIN = 'example.org';

    /** The one file every sample download points to, under storage/app/public. */
    public const FILE = 'design-preview-colleges/sample-document.pdf';

    /** College fields the seeder fills when empty, with their sample values. */
    public static function collegeSamples(): array
    {
        $m = self::MARK;
        $link = fn (string $path) => 'https://' . self::EMAIL_DOMAIN . '/' . $path;

        return [
            'headline' => "{$m}One line on what the college is known for",
            'tagline' => "{$m}One or two sentences under the headline that welcome students and say what sets the college apart.",
            'overview' => "{$m}A short introduction to the college: when it began, the programs it offers, and the kind of graduates it forms. Two or three sentences work best.",
            'email' => 'college@' . self::EMAIL_DOMAIN,
            'vision' => "{$m}The college's approved vision statement, usually one or two sentences.",
            'mission' => "{$m}The college's approved mission statement: whom the college serves, how it teaches, and what it gives back to the community.",
            'goals' => "<ul><li>{$m}First goal of the college</li><li>Second goal, for example on research</li><li>Third goal, for example on community extension</li><li>Fourth goal, for example on values formation</li></ul>",
            'objectives' => "<ol><li>{$m}First objective: what graduates will be able to do</li><li>Second objective</li><li>Third objective</li><li>Fourth objective</li></ol>",
            'dean_message' => "{$m}A short welcome from the dean, in the dean's own words.\n\nIt can say what the college stands for and what students can expect, and invite them to visit or enrol. Three short paragraphs read well on a phone.\n\nA closing line. The dean's name and title appear beside the message.",
            'facilities' => "<p>{$m}The rooms and equipment students use, with a line on each:</p><ul><li><strong>Laboratory or studio</strong>: what students do there</li><li><strong>Library section or reading room</strong>: what it holds</li><li><strong>Training or simulation room</strong>: the equipment in it</li></ul>",
            'research_cluster' => 'SAMPLE',
            'research_cluster_name' => "{$m}Full name of the research cluster",
            'research_description' => "{$m}What the cluster studies and why it matters to the region, in two or three sentences.",
            'research_coordinator' => "{$m}Name of the research coordinator",
            'research_agenda_url' => $link('research-agenda'),
            'research_repository_url' => $link('research-repository'),
            'research_highlights' => [
                ['title' => "{$m}Title of a recent study", 'description' => 'One sentence on what it found and who it helps.', 'url' => $link('study-1')],
                ['title' => "{$m}Title of a paper presented at a conference", 'description' => 'Where and when it was presented.', 'url' => $link('study-2')],
                ['title' => "{$m}Title of a funded research project", 'description' => 'The funding agency and the community it serves.', 'url' => null],
            ],
            'focus_areas' => [
                ['area' => "{$m}First research theme", 'topics' => "A topic under this theme\nAnother topic\nA third topic", 'sdgs' => [4, 9]],
                ['area' => "{$m}Second research theme", 'topics' => "A topic under this theme\nAnother topic", 'sdgs' => [3, 11]],
                ['area' => "{$m}Third research theme", 'topics' => "A topic under this theme\nAnother topic", 'sdgs' => [13, 17]],
            ],
            'partners_intro' => "{$m}One or two sentences on why the college works with partners, and what students gain from them.",
            'partners' => [
                ['name' => "{$m}Name of a partner school", 'type' => 'Partner school', 'scope' => 'international', 'country' => 'Japan', 'period' => 'Since 2023', 'sdgs' => [4, 17],
                    'description' => 'What the partnership does: exchange, joint research or visiting lecturers.', 'links' => [['label' => 'Website', 'url' => $link('partner-1')]]],
                ['name' => "{$m}Name of a government agency", 'type' => 'Government partner', 'scope' => 'local', 'country' => 'Philippines', 'period' => '2025–2026', 'sdgs' => [8, 16],
                    'description' => 'What the agreement covers, for example internships or training.', 'links' => []],
                ['name' => "{$m}Name of a company or hospital", 'type' => 'Industry partner', 'scope' => 'local', 'country' => 'Philippines', 'period' => 'Ongoing', 'sdgs' => [3, 9],
                    'description' => 'Where students train, and what the partner offers graduates.', 'links' => [['label' => 'Website', 'url' => $link('partner-3')]]],
            ],
            'linkages' => [
                ['name' => "{$m}A professional organization", 'description' => 'Membership of the college or its faculty', 'url' => $link('linkage-1')],
                ['name' => "{$m}An accrediting or licensing body", 'description' => 'What the link means for the programs', 'url' => null],
                ['name' => "{$m}A network of schools", 'description' => 'Shared activities or research', 'url' => null],
            ],
            'student_benefits' => [
                ['title' => "{$m}Internships", 'description' => 'Where students train and for how long.', 'url' => null],
                ['title' => "{$m}Exchange and study visits", 'description' => 'Where students can go, and who can apply.', 'url' => null],
                ['title' => "{$m}Scholarships", 'description' => 'Grants that partners offer, and how to apply.', 'url' => null],
                ['title' => "{$m}Jobs after graduation", 'description' => 'Partners that hire or recommend graduates.', 'url' => null],
            ],
            'sdg_summary' => "{$m}How the college serves the Sustainable Development Goals through teaching, research and extension, in two or three sentences.",
            'extension_projects' => [
                ['title' => "{$m}Name of an extension project", 'year' => '2025', 'sdgs' => [4, 10], 'url' => null, 'description' => 'Who it serves, what the college does, and what changed.'],
                ['title' => "{$m}Name of a community training", 'year' => '2025', 'sdgs' => [3, 8], 'url' => null, 'description' => 'Who attended and what they learned.'],
                ['title' => "{$m}Name of an outreach activity", 'year' => '2024', 'sdgs' => [6, 13], 'url' => null, 'description' => 'Where it took place and how many people joined.'],
            ],
        ];
    }

    /** Program fields the seeder fills when empty, with their sample values. */
    public static function programSamples(): array
    {
        $m = self::MARK;

        return [
            'summary' => "{$m}One or two sentences on what students learn and where graduates work. Shown on program cards.",
            'overview' => "<p>{$m}A short introduction to the program: what it covers, how long it takes, and what makes it different at ULS.</p><p>A second paragraph can describe how students learn: laboratories, field work, internships or a thesis.</p>",
            'objectives' => "<p>{$m}By the end of the program, graduates are able to:</p><ol><li>apply the knowledge of the field to real problems;</li><li>communicate clearly in speaking and writing;</li><li>work in teams and lead small projects;</li><li>act ethically and serve the community.</li></ol>",
            'curriculum' => "<h3>{$m}First year</h3><p>The subjects of the first and second semesters.</p><h3>Second year</h3><p>The subjects of each semester.</p><h3>Third year</h3><p>The subjects of each semester, and the internship if there is one.</p><h3>Fourth year</h3><p>The subjects of each semester, and the thesis or capstone project.</p>",
            'careers' => "<p>{$m}Jobs graduates commonly take:</p><ul><li>A first job title</li><li>A second job title</li><li>A third job title</li><li>Graduate studies or teaching</li></ul>",
            'admission_requirements' => "<p>{$m}What applicants submit:</p><ul><li>Report card or transcript of records</li><li>Certificate of good moral character</li><li>Birth certificate</li><li>Two recent ID photos</li></ul>",
            'faqs' => [
                ['question' => "{$m}A question students often ask about the program?", 'answer' => 'The answer, in two or three sentences.'],
                ['question' => "{$m}Is there an entrance exam or interview?", 'answer' => 'What it covers and when it is held.'],
                ['question' => "{$m}Can I shift into this program?", 'answer' => 'Who can shift, and which subjects are credited.'],
            ],
        ];
    }

    public function run(): void
    {
        $m = self::MARK;
        $this->writeSampleFile();

        foreach (College::all() as $college) {
            $this->fillEmpty($college, self::collegeSamples());

            foreach ([1, 2] as $n) {
                Department::updateOrCreate(['slug' => self::SLUG_PREFIX . "{$college->slug}-department-$n"], [
                    'college_id' => $college->id,
                    'name' => "{$m}Department name " . ($n === 1 ? 'one' : 'two'),
                    'overview' => "{$m}What the department teaches and the programs it runs, in two or three sentences.",
                    'achievements' => "<ul><li>{$m}A recent achievement, for example a board exam result</li><li>An award won by students or faculty</li><li>A new laboratory or partnership</li></ul>",
                    'status' => 'published',
                    'sort_order' => 900 + $n,
                ]);
            }

            // Most colleges list only their dean so far: three sample people after them fill out the faculty grid.
            foreach ([['Sample Faculty Name, PhD', 'Program Chair'], ['Sample Faculty Name, MA', 'Faculty'], ['Sample Faculty Name', 'Faculty']] as $i => [$name, $position]) {
                FacultyMember::updateOrCreate(['college_id' => $college->id, 'email' => 'faculty' . ($i + 1) . '@' . self::EMAIL_DOMAIN], [
                    'name' => $name,
                    'position' => $position,
                    'specialization' => 'Field of specialization',
                    'is_published' => true,
                    'sort_order' => 900 + $i,
                ]);
            }

            foreach ([[10, 'Research colloquium', 'Where students and faculty present their studies.'], [24, 'Career orientation', 'Alumni and partners talk about jobs after graduation.']] as [$days, $title, $about]) {
                AcademicEvent::updateOrCreate(['college_id' => $college->id, 'title' => "{$m}$title"], [
                    'starts_at' => now()->addDays($days)->setTime(9, 0),
                    'ends_at' => now()->addDays($days)->setTime(12, 0),
                    'location' => 'Venue on campus',
                    'description' => $about,
                    'is_published' => true,
                ]);
            }

            foreach ([['Curriculum guide', 'curriculum'], ['Student handbook', 'brochure']] as [$title, $category]) {
                Document::updateOrCreate(['college_id' => $college->id, 'title' => "{$m}$title"], [
                    'category' => $category,
                    'file' => self::FILE,
                    'is_public' => true,
                ]);
            }
        }

        Program::all()->each(fn (Program $program) => $this->fillEmpty($program, self::programSamples()));

        $this->command?->info('Sample content added to ' . College::count() . ' colleges and ' . Program::count() . ' programs.');
    }

    /** Fills only the fields that are empty; saving one by one logs it and refreshes cached pages. */
    private function fillEmpty(\Illuminate\Database\Eloquent\Model $model, array $samples): void
    {
        foreach ($samples as $field => $value) {
            if (blank($model->{$field})) {
                $model->{$field} = $value;
            }
        }

        $model->isDirty() && $model->save();
    }

    /** A one-page PDF, so sample downloads open like real ones. */
    private function writeSampleFile(): void
    {
        $target = storage_path('app/public/' . self::FILE);
        if (is_file($target)) {
            return;
        }

        $text = 'BT /F1 20 Tf 72 760 Td (Sample document for the design preview) Tj ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length ' . strlen($text) . " >>\nstream\n$text\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n$body\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";

        File::ensureDirectoryExists(dirname($target));
        File::put($target, $pdf);
    }
}
