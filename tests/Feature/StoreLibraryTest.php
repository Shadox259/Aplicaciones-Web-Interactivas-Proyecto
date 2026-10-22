<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_authenticated_user_can_save_and_remove_visible_books(): void
    {
        $reader = User::factory()->customer()->create();
        $author = User::factory()->author()->create();
        $book = Book::factory()->for($author, 'author')->withPdf()->create();

        $this->actingAs($reader)
            ->post(route('biblioteca.guardar', $book))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('book_user', [
            'user_id' => $reader->id,
            'book_id' => $book->id,
        ]);

        $this->actingAs($reader)
            ->post(route('biblioteca.guardar', $book))
            ->assertSessionHas('info');

        $this->actingAs($reader)
            ->delete(route('biblioteca.quitar', $book))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('book_user', [
            'user_id' => $reader->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_user_cannot_save_own_or_hidden_books_and_guest_is_redirected(): void
    {
        $reader = User::factory()->customer()->create();
        $author = User::factory()->author()->create();
        $ownBook = Book::factory()->for($reader, 'author')->create();
        $draft = Book::factory()->for($author, 'author')->draft()->create();
        $published = Book::factory()->for($author, 'author')->create();

        $this->post(route('biblioteca.guardar', $published))->assertRedirect(route('login'));

        $this->actingAs($reader)
            ->post(route('biblioteca.guardar', $ownBook))
            ->assertSessionHas('info');

        $this->actingAs($reader)
            ->post(route('biblioteca.guardar', $draft))
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('book_user', 0);
    }
}
