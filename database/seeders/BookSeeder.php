<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use App\Services\BookFiles;
use App\Support\SimplePdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $files = app(BookFiles::class);
        $books = require database_path('seeders/data/books.php');

        foreach ($books as $i => $data) {
            if (Book::where('title', $data['title'])->exists()) {
                continue;
            }

            $author = User::where('email', $data['author'])->firstOrFail();
            $genre = Genre::where('name', $data['genre'])->first();
            $published = $data['status'] === Book::STATUS_PUBLISHED;

            $book = $author->books()->create([
                'genre_id' => $genre?->id,
                'title' => $data['title'],
                'slug' => Book::uniqueSlug($data['title']),
                'synopsis' => $data['synopsis'],
                'language' => 'Español',
                'pages' => $data['pages'],
                'status' => $data['status'],
                'published_at' => $published ? now()->subDays(2 + $i * 2) : null,
            ]);

            if ($data['opening']) {
                $paragraphs = array_merge(
                    $data['opening'],
                    DemoText::paragraphs($data['genre'], $data['title'], 14),
                    ['# Fin de la muestra', 'Este es un ejemplar de demostración generado automáticamente. Los libros completos los publican los autores desde su panel.']
                );

                $pdf = SimplePdf::book($data['title'], $author->name, $data['genre'], $paragraphs);
                $book->fill($files->storePdfContents($pdf, Str::slug($data['title']).'.pdf'));
            }

            $book->is_featured = $data['featured'];
            $book->save();
        }
    }
}
