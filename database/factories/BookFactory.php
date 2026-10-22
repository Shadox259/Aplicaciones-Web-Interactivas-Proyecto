<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use App\Services\BookFiles;
use App\Support\SimplePdf;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Por defecto: libro PUBLICADO, sin PDF ni portada subida.
     * Estados: ->draft()  ->featured()  ->withPdf()
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(3), '.');

        return [
            'user_id' => User::factory()->author(),
            'genre_id' => Genre::factory(),
            'title' => $title,
            'slug' => Book::uniqueSlug($title),
            'synopsis' => fake()->paragraph(4),
            'language' => 'Español',
            'pages' => fake()->numberBetween(80, 420),
            'cover_path' => null,
            'pdf_path' => null,
            'pdf_original_name' => null,
            'pdf_size' => 0,
            'status' => Book::STATUS_PUBLISHED,
            'is_featured' => false,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Book::STATUS_DRAFT, 'published_at' => null]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    /** Genera un PDF real (disco "local"); en pruebas usar Storage::fake('local'). */
    public function withPdf(): static
    {
        return $this->afterMaking(function (Book $book) {
            $pdf = SimplePdf::book($book->title, 'Autor de prueba', 'Demo', ['Contenido de prueba para el lector.']);
            $book->fill(app(BookFiles::class)->storePdfContents($pdf, $book->slug.'.pdf'));
        });
    }
}
