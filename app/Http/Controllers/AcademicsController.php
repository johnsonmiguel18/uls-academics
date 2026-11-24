<?php

namespace App\Http\Controllers;

use App\Models\AcademicEvent;
use App\Models\College;
use App\Models\Department;
use App\Models\FacultyMember;
use App\Models\News;
use App\Models\Program;
use App\Models\Term;
use App\Support\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Public Academics pages. What each page shows (sections, order, headings, numbers, limits)
 * comes from Admin → Site settings; lists come from Admin → Lists and categories.
 * Only the sections that are switched on are queried.
 */
class AcademicsController extends Controller
{
    public function index()
    {
        $sections = Site::sections('home');

        $colleges = College::published()
            ->withCount(['programs' => fn (Builder $q) => $q->where('status', 'published')])
            ->get();

        return view('academics.index', [
            'sections' => $sections,
            'colleges' => $colleges,
            'stats' => isset($sections['stats']) ? $this->homeStats($colleges->count()) : [],
            'featured' => isset($sections['featured'])
                ? Program::published()->where('is_featured', true)->with('college', 'accreditations')
                    ->orderBy('sort_order')->take((int) ($sections['featured']['limit'] ?? 6))->get()
                : collect(),
            // Same stories and the same layout as the news page: every published story, university-wide ones included.
            ...(isset($sections['news']) ? $this->newsFront((int) ($sections['news']['limit'] ?? 3)) : ['featuredNews' => null]),
            'events' => isset($sections['news']) ? AcademicEvent::upcoming()->with('college')->take(4)->get() : collect(),
            'recognitions' => isset($sections['recognitions'])
                ? array_slice(self::recognitions(), 0, (int) ($sections['recognitions']['limit'] ?? 6))
                : [],
        ]);
    }

    /**
     * The news page's front layout for the home page: the lead story (newest featured, or newest), three headlines
     * under it, $limit cards and up to five "More headlines" rows.
     */
    private function newsFront(int $limit): array
    {
        $limit = max(3, $limit);
        $hero = News::published()->where('is_featured', true)->latest('published_at')->first()
            ?? News::published()->latest('published_at')->first();
        $rest = $hero ? News::published()->whereKeyNot($hero->id)->latest('published_at')->take(3 + $limit + 5)->get() : collect();
        $related = $rest->count() >= 3 + $limit ? $rest->take(3) : collect();
        $cards = $rest->slice($related->count())->take($limit)->values();

        return [
            'featuredNews' => $hero,
            'related' => $related,
            'cards' => $cards,
            'latestNews' => $rest->slice($related->count() + $cards->count())->take(5)->values(),
        ];
    }

    /** University-wide accreditation and rankings with a name (Site settings → Home page). */
    public static function recognitions(): array
    {
        return array_values(array_filter((array) Site::get('recognitions.items', []), fn ($item) => filled($item['name'] ?? null)));
    }

    /** [label => number] for the numbers chosen in Site settings; zeros are left out. */
    private function homeStats(int $collegeCount): array
    {
        $wanted = collect(Site::get('home.stats', []))->filter(fn ($s) => $s['enabled'] ?? true);
        $metrics = $wanted->pluck('metric')->all();
        $byLevel = $this->needs($metrics, 'level:', 'programs')
            ? Program::published()->selectRaw('level, count(*) as total')->groupBy('level')->pluck('total', 'level')
            : collect();

        return $wanted->mapWithKeys(fn ($stat) => [$stat['label'] => match (true) {
            $stat['metric'] === 'colleges' => $collegeCount,
            $stat['metric'] === 'programs' => $byLevel->sum(),
            $stat['metric'] === 'accredited' => Program::published()->has('accreditations')->count(),
            $stat['metric'] === 'faculty' => FacultyMember::where('is_published', true)->count(),
            str_starts_with($stat['metric'], 'level:') => (int) ($byLevel[substr($stat['metric'], 6)] ?? 0),
            default => 0,
        }])->filter()->all();
    }

    private function needs(array $metrics, string $prefix, string $exact): bool
    {
        return collect($metrics)->contains(fn ($m) => $m === $exact || str_starts_with($m, $prefix));
    }

    public function programs(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'level' => ['nullable', 'in:'.implode(',', array_keys(Term::options('level')))],
            'college' => ['nullable', 'string', 'max:255'],
            'degree' => ['nullable', 'in:'.implode(',', array_keys(Term::options('degree_type')))],
            'accredited' => ['nullable', 'boolean'],
        ]);

        // Level order follows the "Academic levels" list in the admin.
        $levelOrder = collect(array_keys(Term::options('level')))
            ->map(fn ($key, $i) => 'when '.\DB::getPdo()->quote($key).' then '.$i)->implode(' ');

        $programs = Program::published()
            ->with('college', 'accreditations')
            ->when($filters['q'] ?? null, fn (Builder $q, $term) => $q->where(fn (Builder $q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('majors', 'like', "%{$term}%")
                ->orWhere('summary', 'like', "%{$term}%")))
            ->when($filters['level'] ?? null, fn (Builder $q, $level) => $q->where('level', $level))
            ->when($filters['college'] ?? null, fn (Builder $q, $slug) => $q->whereRelation('college', 'slug', $slug))
            ->when($filters['degree'] ?? null, fn (Builder $q, $degree) => $q->where('degree_type', $degree))
            ->when($filters['accredited'] ?? false, fn (Builder $q) => $q->has('accreditations'))
            ->when($levelOrder, fn (Builder $q) => $q->orderByRaw("case level $levelOrder else 999 end"))
            ->orderBy('name')
            ->paginate(max(6, (int) Site::get('finder.per_page', 24)))
            ->withQueryString();

        return view('academics.programs', [
            'programs' => $programs,
            'filters' => $filters,
            'colleges' => College::published()->get(['name', 'slug']),
        ]);
    }

    public function college(College $college)
    {
        abort_unless($college->status === 'published', 404);

        $sections = Site::sections('college');

        // Only load what the switched-on sections show.
        $college->load([
            'departments' => fn ($q) => $q->where('status', 'published'),
            'programs' => fn ($q) => $q->where('status', 'published')->with('accreditations', 'college'),
            ...(isset($sections['faculty']) ? ['faculty'] : []),
            ...(isset($sections['resources']) ? ['documents'] : []),
        ]);

        $storyCount = $college->news()->published()->count();
        $partners = collect($college->partners ?? []);

        $stats = collect(Site::get('college.stats', []))
            ->filter(fn ($s) => $s['enabled'] ?? true)
            ->mapWithKeys(fn ($s) => [$s['label'] => match ($s['metric']) {
                'programs' => $college->programs->count(),
                'partners' => $partners->count(),
                'sdgs' => count($college->sdgsSupported()),
                'stories' => $storyCount,
                'faculty' => $college->relationLoaded('faculty') ? $college->faculty->count() : $college->faculty()->count(),
                default => 0,
            }])->filter()->all();

        return view('academics.college', [
            'college' => $college,
            'sections' => $sections,
            'stats' => $stats,
            'news' => isset($sections['news']) ? $college->news()->published()->latest('published_at')->take(3)->get() : collect(),
            'events' => isset($sections['news']) ? AcademicEvent::upcoming()->where('college_id', $college->id)->take(4)->get() : collect(),
            'recognitions' => isset($sections['accreditation']) ? self::recognitions() : [],
        ]);
    }

    public function department(College $college, Department $department)
    {
        abort_unless($college->status === 'published' && $department->status === 'published', 404);

        $department->load([
            'programs' => fn ($q) => $q->where('status', 'published')->with('accreditations', 'college'),
            'faculty',
            'documents',
        ]);

        return view('academics.department', compact('college', 'department'));
    }

    public function program(Program $program)
    {
        $program->load('college', 'department', 'accreditations');
        abort_unless($program->status === 'published' && $program->college->status === 'published', 404);

        return view('academics.program', [
            'program' => $program,
            'sections' => Site::sections('program'),
            'related' => Program::published()
                ->with('accreditations')
                ->where('college_id', $program->college_id)
                ->whereKeyNot($program->id)
                ->orderBy('sort_order')
                ->take(3)
                ->get()
                ->each->setRelation('college', $program->college), // same college: no second query
        ]);
    }
}
