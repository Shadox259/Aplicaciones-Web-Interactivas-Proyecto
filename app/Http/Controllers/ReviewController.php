<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function store(Request $request, Book $book): RedirectResponse
    {
        $user = $request->user();

        abort_unless(Gate::allows('read', $book), 404);

        if ($book->user_id === $user->id) {
            return back()->with('error', 'No puedes reseñar tus propios libros.');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        Review::updateOrCreate(
            ['user_id' => $user->id, 'book_id' => $book->id],
            ['rating' => $data['rating'], 'comment' => $data['comment'] ?? null],
        );

        return back()->with('success', 'Tu reseña se guardó correctamente.');
    }

    public function destroy(Request $request, Review $review): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $review->user_id === $user->id, 403);

        $review->delete();

        return back()->with('success', 'La reseña se eliminó correctamente.');
    }
}
