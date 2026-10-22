<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookRead;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render_and_drafts_are_hidden_from_public(): void
    {
        $author = User::factory()->author()->create();
        $genre = Genre::factory()->create(['name' => 'Ensayo', 'slug' => 'ensayo']);
        $published = Book::factory()->for($author, 'author')->for($genre)->create([
            'title' => 'Teoría del Archivo',
            'slug' => 'teoria-del-archivo',
        ]);
        $draft = Book::factory()->for($author, 'author')->for($genre)->draft()->create([
            'title' => 'Borrador Interno',
            'slug' => 'borrador-interno',
        ]);

        $this->get(route('inicio'))
            ->assertOk()
            ->assertSee('Bienvenido a Library of Wonderland')
            ->assertSee('Más leídos');

        $this->get(route('catalogo'))
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee($draft->title);

        $this->get(route('libros.show', $published))
            ->assertOk()
            ->assertSee($published->title);

        $this->get(route('autores.show', $author))
            ->assertOk()
            ->assertSee($author->name);

        $this->get(route('libros.show', $draft))->assertNotFound();
    }

    public function test_catalog_filters_and_popular_sorting_work(): void
    {
        $genreEssay = Genre::factory()->create(['name' => 'Ensayo', 'slug' => 'ensayo']);
        $genreNovel = Genre::factory()->create(['name' => 'Novela', 'slug' => 'novela']);
        $authorA = User::factory()->author()->create(['name' => 'Ada Autor']);
        $authorB = User::factory()->author()->create(['name' => 'Bruno Bibliófilo']);

        $popularBook = Book::factory()->for($authorA, 'author')->for($genreEssay)->create([
            'title' => 'Arquitectura del Texto',
            'slug' => 'arquitectura-del-texto',
        ]);
        $ratedBook = Book::factory()->for($authorA, 'author')->for($genreEssay)->create([
            'title' => 'Crítica de Biblioteca',
            'slug' => 'critica-de-biblioteca',
        ]);
        $otherBook = Book::factory()->for($authorB, 'author')->for($genreNovel)->create([
            'title' => 'Sombras del Archivo',
            'slug' => 'sombras-del-archivo',
        ]);

        BookRead::record($popularBook, null, BookRead::TYPE_READ);
        BookRead::record($popularBook, User::factory()->customer()->create(), BookRead::TYPE_READ);
        BookRead::record($popularBook, User::factory()->customer()->create(), BookRead::TYPE_DOWNLOAD);
        BookRead::record($otherBook, User::factory()->customer()->create(), BookRead::TYPE_READ);

        Review::create(['user_id' => User::factory()->customer()->create()->id, 'book_id' => $ratedBook->id, 'rating' => 5, 'comment' => 'Excelente']);
        Review::create(['user_id' => User::factory()->customer()->create()->id, 'book_id' => $otherBook->id, 'rating' => 3, 'comment' => 'Correcto']);

        $this->get(route('catalogo', [
            'q' => 'Arquitectura',
            'genero' => 'ensayo',
            'autor' => $authorA->id,
        ]))
            ->assertOk()
            ->assertSee($popularBook->title)
            ->assertDontSee($ratedBook->title)
            ->assertDontSee($otherBook->title);

        $this->get(route('catalogo', ['orden' => 'populares']))
            ->assertOk()
            ->assertSeeInOrder([$popularBook->title, $otherBook->title]);

        $this->get(route('catalogo', ['orden' => 'valorados']))
            ->assertOk()
            ->assertSeeInOrder([$ratedBook->title, $otherBook->title]);
    }
}
