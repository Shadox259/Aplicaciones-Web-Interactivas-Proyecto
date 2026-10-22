<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $baseQuery = Book::published()
            ->with(['author:id,name', 'genre:id,name,slug'])
            ->withRating()
            ->withStats();

        $featuredBooks = (clone $baseQuery)
            ->featured()
            ->latest('published_at')
            ->take(5)
            ->get();

        if ($featuredBooks->isEmpty()) {
            $featuredBooks = (clone $baseQuery)
                ->latest('published_at')
                ->take(5)
                ->get();
        }

        $newBooks = (clone $baseQuery)
            ->whereNotIn('id', $featuredBooks->modelKeys())
            ->latest('published_at')
            ->take(8)
            ->get();

        $topRatedBooks = (clone $baseQuery)
            ->has('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->latest('published_at')
            ->take(4)
            ->get();

        $mostReadBooks = (clone $baseQuery)
            ->orderByDesc('reads_count')
            ->orderByDesc('downloads_count')
            ->latest('published_at')
            ->take(5)
            ->get();

        $decorativeBooks = $featuredBooks->take(3)->values();

        $genres = Genre::query()
            ->whereHas('publishedBooks')
            ->withCount(['publishedBooks as books_count'])
            ->orderByDesc('books_count')
            ->orderBy('name')
            ->take(10)
            ->get(['id', 'name', 'slug']);

        return view('store.home', [
            'featuredBooks' => $featuredBooks,
            'newBooks' => $newBooks,
            'topRatedBooks' => $topRatedBooks,
            'mostReadBooks' => $mostReadBooks,
            'decorativeBooks' => $decorativeBooks,
            'genres' => $genres,
            'stats' => [
                'books' => Book::published()->count(),
                'authors' => Book::published()->distinct()->count('user_id'),
                'genres' => Genre::query()->whereHas('publishedBooks')->count(),
                'reviews' => Review::query()->count(),
            ],
        ]);
    }
}
