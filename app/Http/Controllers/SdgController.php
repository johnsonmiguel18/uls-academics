<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\News;
use App\Support\Sdg;
use Illuminate\Support\Collection;

/**
 * SDG report (/sdgs) and one page per goal (/sdgs/{goal}). Counts come from published stories'
 * SDG tags and the extension projects on each published college page; texts from Site settings → SDG pages.
 */
class SdgController extends Controller
{
    public function index()
    {
        [$colleges, $items] = $this->items();

        $counts = $items->flatMap(fn ($item) => $item['sdgs'])->countBy()->all();
        $byCollege = $colleges
            ->map(function (College $college) use ($items) {
                $own = $items->where('college_id', $college->id);

                return ['college' => $college, 'goals' => $own->flatMap(fn ($i) => $i['sdgs'])->unique()->sort()->values()->all(), 'items' => $own->count()];
            })
            ->filter(fn ($row) => $row['items'] > 0)
            ->sortByDesc('items')
            ->values();

        return view('sdgs.index', [
            'counts' => $counts,
            'byCollege' => $byCollege,
            'stats' => ['goals' => count($counts), 'items' => $items->count(), 'colleges' => $byCollege->count()],
        ]);
    }

    public function show(int $goal)
    {
        $goals = Sdg::goals();
        abort_unless(isset($goals[$goal]), 404);

        [$colleges, $items] = $this->items();
        $tagged = $items->filter(fn ($item) => in_array($goal, $item['sdgs'], true));

        $collegeCounts = $tagged->whereNotNull('college_id')->countBy('college_id');
        $involved = $colleges->filter(fn ($c) => $collegeCounts->has($c->id))->sortByDesc(fn ($c) => $collegeCounts[$c->id])->values();

        $stories = News::query()->published()->forSdg($goal)->latest('published_at');

        return view('sdgs.show', [
            'goal' => $goal,
            'goalInfo' => $goals[$goal],
            'description' => \App\Models\Term::entries('sdg')[(string) $goal]['description'] ?? null,
            'involved' => $involved,
            'collegeCounts' => $collegeCounts,
            'projects' => $tagged->where('type', 'project')->values(),
            'stories' => (clone $stories)->take(6)->get(),
            'storyCount' => $stories->count(),
            'previous' => array_key_last(array_filter($goals, fn ($n) => $n < $goal, ARRAY_FILTER_USE_KEY)),
            'next' => array_key_first(array_filter($goals, fn ($n) => $n > $goal, ARRAY_FILTER_USE_KEY)),
        ]);
    }

    /**
     * Published colleges, and every SDG-tagged item: published stories plus college extension projects.
     * Two queries, no matter how many colleges or stories.
     */
    private function items(): array
    {
        $colleges = College::query()->published()->get(['id', 'name', 'short_name', 'slug', 'extension_projects']);
        $goals = fn ($tags) => collect($tags ?? [])->map(fn ($n) => (int) $n)->filter()->unique()->values()->all();

        $stories = News::query()->published()->whereNotNull('sdgs')->get(['id', 'college_id', 'sdgs'])
            ->map(fn (News $n) => ['type' => 'story', 'college_id' => $n->college_id, 'sdgs' => $goals($n->sdgs)]);

        $projects = $colleges->flatMap(fn (College $c) => collect($c->extension_projects ?? [])
            ->map(fn ($p) => ['type' => 'project', 'college_id' => $c->id, 'college' => $c, 'project' => $p, 'sdgs' => $goals($p['sdgs'] ?? [])]));

        /** @var Collection $items */
        $items = $stories->concat($projects)->filter(fn ($item) => $item['sdgs'])->values();

        return [$colleges, $items];
    }
}
