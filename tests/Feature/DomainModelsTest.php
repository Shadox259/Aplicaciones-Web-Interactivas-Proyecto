<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookRead;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_slugs_are_unique_and_stable(): void
    {
        $first = Book::factory()->create(['title' => 'Mi Libro', 'slug' => Book::uniqueSlug('Mi Libro')]);
        $this->assertSame('mi-libro', $first->slug);
        $this->assertSame('mi-libro-2', Book::uniqueSlug('Mi Libro'));
        $this->assertSame('mi-libro', Book::uniqueSlug('Mi Libro', $first->id));
        $this->assertSame('libro', Book::uniqueSlug('¡¡¡'));
    }

    public function test_rating_and_activity_counters(): void
    {
        $book = Book::factory()->create();
        $this->assertSame(0.0, $book->rating);
        $this->assertSame(0, $book->reviews_total);
        $this->assertSame(0, $book->reads_total);

        Review::create(['user_id' => User::factory()->create()->id, 'book_id' => $book->id, 'rating' => 5]);
        Review::create(['user_id' => User::factory()->create()->id, 'book_id' => $book->id, 'rating' => 4]);
        BookRead::create(['book_id' => $book->id, 'type' => 'read']);
        BookRead::create(['book_id' => $book->id, 'type' => 'read']);
        BookRead::create(['book_id' => $book->id, 'type' => 'download']);
        $book->savedBy()->attach(User::factory()->create()->id);

        $fresh = Book::withRating()->withStats()->find($book->id);
        $this->assertSame(4.5, $fresh->rating);
        $this->assertSame(2, $fresh->reviews_total);
        $this->assertSame(2, $fresh->reads_total);
        $this->assertSame(1, $fresh->downloads_total);
        $this->assertSame(1, $fresh->saves_total);

        $lazy = $book->fresh();
        $this->assertSame(2, $lazy->reads_total);
        $this->assertSame(1, $lazy->downloads_total);
        $this->assertSame(1, $lazy->saves_total);
    }

    public function test_role_and_visibility_rules(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->author()->create();
        $other = User::factory()->author()->create();
        $customer = User::factory()->create();
        $draft = Book::factory()->draft()->create(['user_id' => $author->id]);
        $published = Book::factory()->create(['user_id' => $author->id]);

        $this->assertTrue($admin->canPublish());
        $this->assertTrue($author->canPublish());
        $this->assertFalse($customer->canPublish());
        $this->assertSame('panel.dashboard', $author->homeRoute());
        $this->assertSame('inicio', $customer->homeRoute());

        $this->assertTrue($draft->isVisibleTo($admin));
        $this->assertTrue($draft->isVisibleTo($author));
        $this->assertFalse($draft->isVisibleTo($other));
        $this->assertFalse($draft->isVisibleTo(null));
        $this->assertTrue($published->isVisibleTo(null));
    }

    public function test_read_permission_is_public_for_published_books_only(): void
    {
        $author = User::factory()->author()->create();
        $published = Book::factory()->create(['user_id' => $author->id]);
        $draft = Book::factory()->draft()->create(['user_id' => $author->id]);
        $stranger = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->assertTrue($stranger->can('read', $published));
        $this->assertFalse($stranger->can('read', $draft));
        $this->assertTrue($author->can('read', $draft));
        $this->assertTrue($admin->can('read', $draft));

        $this->assertTrue($author->can('manage', $published));
        $this->assertTrue($admin->can('manage', $published));
        $this->assertFalse($stranger->can('manage', $published));
    }

    public function test_saving_books_to_the_library(): void
    {
        $reader = User::factory()->create();
        $book = Book::factory()->create();

        $this->assertFalse($reader->hasSaved($book));

        $reader->library()->attach($book->id);
        $reader->flushCaches();

        $this->assertTrue($reader->hasSaved($book));
        $this->assertSame([$book->id], $reader->savedBookIds());
    }

    public function test_reads_by_the_author_are_not_counted(): void
    {
        $author = User::factory()->author()->create();
        $reader = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $author->id]);

        $this->assertNull(BookRead::record($book, $author));
        $this->assertNotNull(BookRead::record($book, $reader));
        $this->assertNotNull(BookRead::record($book, null, BookRead::TYPE_DOWNLOAD));

        $this->assertSame(1, $book->fresh()->reads_total);
        $this->assertSame(1, $book->fresh()->downloads_total);
    }

    public function test_role_is_not_mass_assignable(): void
    {
        $user = User::create(['name' => 'Intruso', 'email' => 'x@example.com', 'password' => 'secret123', 'role' => 'admin', 'is_active' => false]);

        $this->assertSame('cliente', $user->fresh()->role);
        $this->assertTrue($user->fresh()->is_active);
    }
}
