<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $query = News::query()->published();

        // Search by title, excerpt, or article content.
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Links from a college page: its stories, optionally for one SDG.
        if ($request->filled('college')) {
            $query->whereRelation('college', 'slug', $request->string('college')->value());
        }

        if ($request->integer('sdg') >= 1 && $request->integer('sdg') <= 17) {
            $query->forSdg($request->integer('sdg'));
        }

        // Filter by category.
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        // The hero is the newest featured story, or else the newest story. It is shown on the unfiltered
        // first page and left out of the unfiltered card grid on every page, so page 2 does not repeat it.
        $hero = ! $request->anyFilled(['search', 'category', 'college', 'sdg'])
            ? (News::query()->published()->where('is_featured', true)->latest('published_at')->first()
                ?? News::query()->published()->latest('published_at')->first())
            : null;
        $featuredNews = $request->integer('page', 1) === 1 ? $hero : null;

        if ($hero) {
            $query->where('id', '!=', $hero->id);
        }

        $news = $query
            ->latest('published_at')
            ->paginate(max(3, (int) \App\Support\Site::get('news.per_page', 9)))
            ->withQueryString();

        // Sidebar rows on the unfiltered front page: the newest stories that are not already the hero
        // or a card on this page. Filtered lists show only their matches, so they get no sidebar.
        $latestNews = $hero
            ? News::query()
                ->published()
                ->whereNotIn('id', $news->pluck('id')->push($hero->id))
                ->latest('published_at')
                ->take(6)
                ->get()
            : collect();

        return view('news.index', compact('news', 'featuredNews', 'latestNews'));
    }

    public function show(string $slug)
    {
        $article = News::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedNews = News::query()
            ->published()
            ->where('id', '!=', $article->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        // Neighbours by publication date, for the "previous / next story" links under the article.
        $date = $article->published_at ?? $article->created_at;

        $previousNews = News::query()->published()
            ->where('id', '!=', $article->id)
            ->where('published_at', '<', $date)
            ->latest('published_at')
            ->first();

        $nextNews = News::query()->published()
            ->where('id', '!=', $article->id)
            ->where('published_at', '>', $date)
            ->oldest('published_at')
            ->first();

        return view('news.show', compact('article', 'relatedNews', 'previousNews', 'nextNews'));
    }
}
