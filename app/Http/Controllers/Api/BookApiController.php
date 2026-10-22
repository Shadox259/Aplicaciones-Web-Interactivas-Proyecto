<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookApiController extends Controller
{
    /** GET /api/v1/libros  -  catalogo publico paginado (solo libros publicados). */
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'genero' => ['nullable', 'string', 'max:100'],
            'orden' => ['nullable', 'in:recientes,valorados,populares,titulo'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Book::published()
            ->with(['author:id,name', 'genre:id,name,slug'])
            ->withRating()
            ->withStats()
            ->search($data['q'] ?? null)
            ->when($data['genero'] ?? null, fn ($q, $slug) => $q->whereHas('genre', fn ($g) => $g->where('slug', $slug)));

        match ($data['orden'] ?? 'recientes') {
            'valorados' => $query->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count'),
            'populares' => $query->orderByDesc('reads_count')->orderBy('title'),
            'titulo' => $query->orderBy('title'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };

        return BookResource::collection(
            $query->paginate($data['por_pagina'] ?? 12)->withQueryString()
        );
    }

    /** GET /api/v1/libros/{slug} */
    public function show(Book $book): BookResource
    {
        abort_unless($book->isPublished(), 404);

        return new BookResource($book->load(['author:id,name', 'genre:id,name,slug'])->loadCount('reviews')->loadAvg('reviews', 'rating')->loadCount(['reads as reads_count' => fn ($q) => $q->where('type', 'read')])->loadCount('savedBy as saves_count'));
    }
}
