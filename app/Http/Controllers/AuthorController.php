<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\User;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function show(User $author): View
    {
        abort_unless($author->canPublish(), 404);

        $books = $author->books()
            ->published()
            ->with(['author:id,name', 'genre:id,name,slug'])
            ->withRating()
            ->withStats()
            ->latest('published_at')
            ->paginate(config('wonderland.per_page'))
            ->withQueryString();

        $publishedBooksCount = $author->books()->published()->count();
        $averageRating = Review::query()
            ->whereIn('book_id', $author->books()->published()->select('id'))
            ->avg('rating');
        $readsTotal = $author->books()->published()->withStats()->get()->sum('reads_total');

        return view('store.author', [
            'author' => $author,
            'books' => $books,
            'publishedBooksCount' => $publishedBooksCount,
            'averageRating' => $averageRating ? round((float) $averageRating, 1) : null,
            'readsTotal' => $readsTotal,
        ]);
    }
}
