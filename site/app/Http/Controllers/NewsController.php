<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function index(Request $request): Response
    {
        $news = News::query()
            ->published()
            ->orderByDesc('published_at')
            ->paginate(12, ['id', 'title', 'slug', 'excerpt', 'image', 'published_at']);

        $news->through(fn (News $item) => [
            'id' => $item->id,
            'title' => $item->title,
            'slug' => $item->slug,
            'excerpt' => $item->excerpt,
            'image' => $item->image,
            'date' => $item->published_at?->format('d.m.Y'),
        ]);

        return Inertia::render('news/Index', [
            'news' => $news,
        ]);
    }

    public function show(News $news): Response
    {
        if (! $news->published_at || $news->published_at->isFuture()) {
            abort(404);
        }

        return Inertia::render('news/Show', [
            'news' => [
                'id' => $news->id,
                'title' => $news->title,
                'slug' => $news->slug,
                'body' => \Illuminate\Support\Str::markdown($news->body),
                'image' => $news->image,
                'date' => $news->published_at?->format('d.m.Y'),
            ],
        ]);
    }
}
