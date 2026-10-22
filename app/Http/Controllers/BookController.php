<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookRead;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BookController extends Controller
{
    public function show(Book $book): View
    {
        abort_unless(Gate::allows('read', $book), 404);

        $book->load([
            'author:id,name,bio,role,is_active',
            'genre:id,name,slug',
            'reviews' => fn ($query) => $query->with('user:id,name')->latest(),
        ])->loadAvg('reviews', 'rating')->loadCount('reviews')->loadCount([
            'reads as reads_count' => fn ($query) => $query->where('type', BookRead::TYPE_READ),
            'reads as downloads_count' => fn ($query) => $query->where('type', BookRead::TYPE_DOWNLOAD),
            'savedBy as saves_count',
        ]);

        $user = auth()->user();
        $userReview = $user ? $book->reviews->firstWhere('user_id', $user->id) : null;
        $canReview = $user && $user->id !== $book->user_id;
        $totalReviews = max(1, $book->reviews->count());
        $reviewDistribution = collect(range(5, 1))
            ->mapWithKeys(function (int $rating) use ($book, $totalReviews) {
                $count = $book->reviews->where('rating', $rating)->count();

                return [$rating => [
                    'count' => $count,
                    'percent' => $book->reviews->isEmpty() ? 0 : (int) round(($count / $totalReviews) * 100),
                ]];
            });

        $moreByAuthor = Book::published()
            ->where('user_id', $book->user_id)
            ->whereKeyNot($book->id)
            ->with(['author:id,name', 'genre:id,name,slug'])
            ->withRating()
            ->withStats()
            ->latest('published_at')
            ->take(4)
            ->get();

        $similarBooks = collect();

        if ($book->genre_id) {
            $similarBooks = Book::published()
                ->where('genre_id', $book->genre_id)
                ->whereKeyNot($book->id)
                ->whereNotIn('id', $moreByAuthor->modelKeys())
                ->with(['author:id,name', 'genre:id,name,slug'])
                ->withRating()
                ->withStats()
                ->orderByDesc('reads_count')
                ->orderByDesc('reviews_avg_rating')
                ->take(4)
                ->get();
        }

        return view('store.book', [
            'book' => $book,
            'userReview' => $userReview,
            'canReview' => $canReview,
            'reviewDistribution' => $reviewDistribution,
            'moreByAuthor' => $moreByAuthor,
            'similarBooks' => $similarBooks,
        ]);
    }
}
