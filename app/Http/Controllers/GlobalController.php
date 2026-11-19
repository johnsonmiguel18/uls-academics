<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\News;
use App\Models\PartnershipInquiry;
use App\Support\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Global Engagement & Linkages (global.<domain>, or /global without GLOBAL_DOMAIN).
 * Texts and sections: Site settings → Global engagement. Partners: each college's partner list
 * plus the university-wide list in those settings, so a partner is entered once.
 */
class GlobalController extends Controller
{
    public function index()
    {
        $sections = Site::sections('global');
        $partners = isset($sections['partners']) ? self::partners() : collect();

        $stories = isset($sections['stories'])
            ? News::query()->published()
                ->when(Site::get('global.story_categories'), fn ($q, $categories) => $q->whereIn('category', $categories))
                ->latest('published_at')
                ->take((int) ($sections['stories']['limit'] ?? 3))
                ->get()
            : collect();

        return view('global.index', [
            'sections' => $sections,
            'partners' => $partners,
            'stats' => [
                'partners' => $partners->count(),
                'local' => $partners->where('scope', 'local')->count(),
                'international' => $partners->where('scope', 'international')->count(),
                'colleges' => $partners->pluck('college')->filter()->unique('id')->count(),
            ],
            'colleges' => $partners->pluck('college')->filter()->unique('id')->sortBy('sort_order')->values(),
            'stories' => $stories,
        ]);
    }

    public function inquire(Request $request)
    {
        // Bots fill the hidden "website" field; pretend success so they move on.
        if ($request->filled('website')) {
            return redirect(route('global.home') . '#connect')->with('inquiry_sent', true);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'organization' => ['nullable', 'string', 'max:190'],
            'interest' => ['nullable', 'string', 'in:' . implode(',', array_keys(Site::get('global.interests', [])))],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        PartnershipInquiry::create($data);

        return redirect(route('global.home') . '#connect')->with('inquiry_sent', true);
    }

    /** Every published partner: college partners first by college order, then university-wide ones. */
    public static function partners(): Collection
    {
        $colleges = College::query()->published()->orderBy('sort_order')->get(['id', 'name', 'short_name', 'slug', 'sort_order', 'partners']);

        return $colleges
            ->flatMap(fn (College $college) => collect($college->partners ?? [])->map(fn ($p) => $p + ['college' => $college]))
            ->concat(collect(Site::get('global.partners', []))->map(fn ($p) => $p + ['college' => null]))
            ->filter(fn ($p) => filled($p['name'] ?? null))
            ->map(fn ($p) => ['scope' => $p['scope'] ?? 'local'] + $p)
            ->values();
    }
}
