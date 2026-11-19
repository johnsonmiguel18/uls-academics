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

        // Only show the featured story on the unfiltered first page.
        $featuredNews = null;

        if (
            ! $request->filled('search')
            && ! $request->filled('category')
            && ! $request->filled('college')
            && ! $request->filled('sdg')
            && $request->integer('page', 1) === 1
        ) {
            $featuredNews = News::query()
                ->published()
                ->where('is_featured', true)
                ->latest('published_at')
                ->first();

            // Keep the featured article out of the regular news grid.
            if ($featuredNews) {
                $query->where('id', '!=', $featuredNews->id);
            }
        }

        $news = $query
            ->latest('published_at')
            ->paginate(max(3, (int) \App\Support\Site::get('news.per_page', 9)))
            ->withQueryString();

        return view('news.index', compact('news', 'featuredNews'));
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

        return view('news.show', compact('article', 'relatedNews'));
    }
}
