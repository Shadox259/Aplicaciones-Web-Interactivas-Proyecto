<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = trim((string) $request->string('q'));

        $books = $user->library()
            ->with(['author:id,name', 'genre:id,name,slug'])
            ->withRating()
            ->withStats()
            ->when($query !== '', fn ($builder) => $builder->search($query))
            ->orderByPivot('created_at', 'desc')
            ->paginate(config('wonderland.per_page'))
            ->withQueryString();

        $publishedBooks = $user->canPublish()
            ? $user->books()
                ->published()
                ->with(['author:id,name', 'genre:id,name,slug'])
                ->withRating()
                ->withStats()
                ->latest('published_at')
                ->get()
            : collect();

        return view('store.library', [
            'books' => $books,
            'publishedBooks' => $publishedBooks,
            'query' => $query,
        ]);
    }

    public function store(Request $request, Book $book): RedirectResponse
    {
        $user = $request->user();

        if (! $book->isVisibleTo($user)) {
            return back()->with('warning', 'No puedes guardar este libro en tu biblioteca.');
        }

        if ($book->user_id === $user->id) {
            return back()->with('info', 'No puedes guardar tus propios libros en tu biblioteca.');
        }

        if ($user->hasSaved($book)) {
            return back()->with('info', 'Este libro ya está en tu biblioteca.');
        }

        $user->library()->syncWithoutDetaching([$book->id]);
        $user->flushCaches();

        return back()->with('success', 'Libro guardado en tu biblioteca.');
    }

    public function destroy(Request $request, Book $book): RedirectResponse
    {
        $user = $request->user();
        $removed = $user->library()->detach($book->id) > 0;
        $user->flushCaches();

        return back()->with($removed ? 'success' : 'info', $removed
            ? 'Libro retirado de tu biblioteca.'
            : 'Ese libro ya no estaba guardado en tu biblioteca.');
    }
}
