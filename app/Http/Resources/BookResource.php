<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Libro en JSON. Incluye los mismos campos que usaba el frontend Angular original
 * (id, title, author, genre, cover_image) mas algunos extras. Todo es gratuito: no hay precios.
 *
 * @mixin \App\Models\Book
 */
class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'author' => $this->author?->name,
            'author_id' => $this->user_id,
            'genre' => $this->genre?->name,
            'genre_slug' => $this->genre?->slug,
            'cover_image' => $this->cover_url,
            'synopsis' => $this->synopsis,
            'language' => $this->language,
            'pages' => $this->pages,
            'rating' => $this->rating,
            'reviews_count' => $this->reviews_total,
            'reads_count' => $this->reads_total,
            'saves_count' => $this->saves_total,
            'is_featured' => $this->is_featured,
            'published_at' => $this->published_at?->toIso8601String(),
            'url' => route('libros.show', $this->resource),
            'read_url' => route('leer.show', $this->resource),
            'pdf_url' => $this->hasPdf() ? route('leer.pdf', $this->resource) : null,
        ];
    }
}
