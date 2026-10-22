<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'genero' => ['nullable', 'string', 'max:120'],
            'orden' => ['nullable', Rule::in(['recientes', 'valorados', 'populares', 'titulo'])],
            'autor' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $books = Book::published()
            ->with(['author:id,name', 'genre:id,name,slug'])
            ->withRating()
            ->withStats()
            ->search($filters['q'] ?? null)
            ->when(filled($filters['genero'] ?? null), function ($query) use ($filters) {
                $query->whereHas('genre', fn ($genre) => $genre->where('slug', $filters['genero']));
            })
            ->when(filled($filters['autor'] ?? null), fn ($query) => $query->where('user_id', $filters['autor']));

        match ($filters['orden'] ?? 'recientes') {
            'valorados' => $books->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count')->orderBy('title'),
            'populares' => $books->orderByDesc('reads_count')->orderByDesc('downloads_count')->orderBy('title'),
            'titulo' => $books->orderBy('title'),
            default => $books->latest('published_at')->latest('id'),
        };

        $books = $books->paginate(config('wonderland.per_page'))->withQueryString();

        $genres = Genre::query()
            ->whereHas('publishedBooks')
            ->withCount(['publishedBooks as books_count'])
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $authors = User::query()
            ->whereIn('role', [User::ROLE_AUTHOR, User::ROLE_ADMIN])
            ->where('is_active', true)
            ->whereHas('books', fn ($query) => $query->published())
            ->withCount(['books as published_books_count' => fn ($query) => $query->published()])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('store.catalog', [
            'books' => $books,
            'genres' => $genres,
            'authors' => $authors,
            'filters' => [
                'q' => $filters['q'] ?? '',
                'genero' => $filters['genero'] ?? '',
                'orden' => $filters['orden'] ?? 'recientes',
                'autor' => isset($filters['autor']) ? (string) $filters['autor'] : '',
            ],
        ]);
    }
}
