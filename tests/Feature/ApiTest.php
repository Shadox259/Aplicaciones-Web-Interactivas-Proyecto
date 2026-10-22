<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookRead;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_index_lists_only_published_books(): void
    {
        Book::factory()->count(3)->create();
        Book::factory()->draft()->create(['title' => 'Borrador secreto']);

        $response = $this->getJson('/api/v1/libros')->assertOk();

        $response->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'slug', 'title', 'author', 'genre', 'cover_image', 'synopsis', 'rating', 'reviews_count', 'reads_count', 'url', 'read_url']],
                'links',
                'meta' => ['current_page', 'total'],
            ]);

        $this->assertStringNotContainsString('Borrador secreto', $response->getContent());
        $this->assertStringNotContainsString('"price"', $response->getContent());
    }

    public function test_books_can_be_filtered_and_sorted(): void
    {
        $fantasia = Genre::factory()->create(['name' => 'Fantasía', 'slug' => 'fantasia']);
        Book::factory()->create(['title' => 'Zeta', 'genre_id' => $fantasia->id]);
        Book::factory()->create(['title' => 'Alfa', 'genre_id' => $fantasia->id]);
        Book::factory()->create(['title' => 'Otro']);

        $this->getJson('/api/v1/libros?genero=fantasia&orden=titulo')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Alfa')
            ->assertJsonPath('data.1.title', 'Zeta')
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/v1/libros?q=Otro')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_popular_order_uses_reads(): void
    {
        $quiet = Book::factory()->create(['title' => 'Poco leido']);
        $popular = Book::factory()->create(['title' => 'Muy leido']);
        BookRead::create(['book_id' => $popular->id, 'type' => 'read']);
        BookRead::create(['book_id' => $popular->id, 'type' => 'read']);
        BookRead::create(['book_id' => $quiet->id, 'type' => 'read']);
        BookRead::create(['book_id' => $quiet->id, 'type' => 'download']);

        $this->getJson('/api/v1/libros?orden=populares')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Muy leido')
            ->assertJsonPath('data.0.reads_count', 2)
            ->assertJsonPath('data.1.reads_count', 1);
    }

    public function test_invalid_parameters_return_json_errors(): void
    {
        $this->getJson('/api/v1/libros?por_pagina=500')->assertStatus(422)->assertJsonValidationErrors('por_pagina');
        $this->get('/api/v1/libros?orden=inventado', ['Accept' => 'text/html'])->assertStatus(422)->assertJsonStructure(['message', 'errors']);
    }

    public function test_show_returns_published_book_and_hides_drafts(): void
    {
        $book = Book::factory()->withPdf()->create(['title' => 'El libro visible']);
        $draft = Book::factory()->draft()->create();

        $this->getJson("/api/v1/libros/{$book->slug}")
            ->assertOk()
            ->assertJsonPath('data.title', 'El libro visible')
            ->assertJsonPath('data.slug', $book->slug)
            ->assertJsonPath('data.pdf_url', route('leer.pdf', $book));

        $this->getJson("/api/v1/libros/{$draft->slug}")->assertNotFound()->assertJsonStructure(['message']);
        $this->getJson('/api/v1/libros/no-existe')->assertNotFound();
    }

    public function test_genres_endpoint_counts_published_books(): void
    {
        $genre = Genre::factory()->create(['name' => 'Misterio', 'slug' => 'misterio']);
        Book::factory()->count(2)->create(['genre_id' => $genre->id]);
        Book::factory()->draft()->create(['genre_id' => $genre->id]);

        $this->getJson('/api/v1/generos')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'misterio', 'books_count' => 2]);
    }

    public function test_api_sends_cors_headers(): void
    {
        $this->getJson('/api/v1/generos', ['Origin' => 'http://localhost:4200'])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*');
    }
}
