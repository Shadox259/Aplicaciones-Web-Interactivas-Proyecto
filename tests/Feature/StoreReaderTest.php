<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookRead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreReaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_published_books_are_public_and_record_reads_and_downloads(): void
    {
        $author = User::factory()->author()->create();
        $reader = User::factory()->customer()->create();
        $book = Book::factory()->for($author, 'author')->withPdf()->create();

        $guestResponse = $this->get(route('leer.show', $book));
        $guestResponse->assertOk()->assertSee($book->title);
        $this->assertDatabaseHas('book_reads', ['book_id' => $book->id, 'user_id' => null, 'type' => BookRead::TYPE_READ]);

        $pdfResponse = $this->get(route('leer.pdf', $book));
        $pdfResponse->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('public', (string) $pdfResponse->headers->get('Cache-Control'));
        $this->assertDatabaseCount('book_reads', 1);

        $downloadResponse = $this->actingAs($reader)->get(route('leer.descargar', $book));
        $downloadResponse->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('public', (string) $downloadResponse->headers->get('Cache-Control'));
        $this->assertDatabaseHas('book_reads', ['book_id' => $book->id, 'user_id' => $reader->id, 'type' => BookRead::TYPE_DOWNLOAD]);
    }

    public function test_drafts_return_404_to_guest_and_other_users_but_author_and_admin_can_access_them(): void
    {
        $author = User::factory()->author()->create();
        $admin = User::factory()->admin()->create();
        $otherReader = User::factory()->customer()->create();
        $draft = Book::factory()->for($author, 'author')->draft()->withPdf()->create();

        $this->get(route('leer.show', $draft))->assertNotFound();
        $this->actingAs($otherReader)->get(route('leer.show', $draft))->assertNotFound();

        $authorResponse = $this->actingAs($author)->get(route('leer.show', $draft));
        $authorResponse->assertOk();
        $this->assertDatabaseCount('book_reads', 0);

        $adminResponse = $this->actingAs($admin)->get(route('leer.pdf', $draft));
        $adminResponse->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('private', (string) $adminResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $adminResponse->headers->get('Cache-Control'));
    }
}
