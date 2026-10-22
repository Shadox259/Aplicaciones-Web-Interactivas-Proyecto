<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookRead;
use App\Models\Genre;
use App\Models\User;
use App\Services\BookFiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PanelBookTest extends TestCase
{
    use RefreshDatabase;

    private ?string $coversPath = null;

    protected function tearDown(): void
    {
        if ($this->coversPath && File::isDirectory($this->coversPath)) {
            File::deleteDirectory($this->coversPath);
        }

        parent::tearDown();
    }

    public function test_author_can_create_book_with_pdf_and_cover(): void
    {
        $this->fakeBookStorage();

        $author = User::factory()->author()->create();
        $genre = Genre::factory()->create();
        Book::factory()->for($author, 'author')->create([
            'title' => 'Libro único',
            'slug' => Book::uniqueSlug('Libro único'),
        ]);

        $response = $this->actingAs($author)->post(route('panel.libros.store'), [
            'title' => 'Libro único',
            'synopsis' => 'Sinopsis de prueba para el panel.',
            'genre_id' => $genre->id,
            'language' => 'Español',
            'pages' => 123,
            'status' => Book::STATUS_PUBLISHED,
            'cover' => UploadedFile::fake()->image('c.jpg'),
            'pdf' => UploadedFile::fake()->create('libro.pdf', 200, 'application/pdf'),
        ]);

        $book = Book::query()->where('title', 'Libro único')->latest('id')->firstOrFail();

        $response->assertRedirect(route('panel.libros.edit', $book));
        $this->assertSame($author->id, $book->user_id);
        $this->assertSame('libro-unico-2', $book->slug);
        $this->assertNotNull($book->published_at);
        Storage::disk('local')->assertExists($book->pdf_path);
        $this->assertFileExists($this->coverAbsolutePath($book->cover_path));
    }

    public function test_author_cannot_edit_delete_or_publish_foreign_books(): void
    {
        $author = User::factory()->author()->create();
        $otherAuthor = User::factory()->author()->create();
        $book = Book::factory()->for($otherAuthor, 'author')->create();

        $this->actingAs($author)->get(route('panel.libros.edit', $book))->assertForbidden();
        $this->actingAs($author)->put(route('panel.libros.update', $book), [
            'title' => 'Cambio',
            'synopsis' => 'Cambio',
            'language' => 'Español',
            'status' => Book::STATUS_DRAFT,
        ])->assertForbidden();
        $this->actingAs($author)->patch(route('panel.libros.publicar', $book))->assertForbidden();
        $this->actingAs($author)->delete(route('panel.libros.destroy', $book))->assertForbidden();
    }

    public function test_creating_published_book_without_pdf_fails(): void
    {
        $author = User::factory()->author()->create();

        $this->actingAs($author)
            ->from(route('panel.libros.create'))
            ->post(route('panel.libros.store'), [
                'title' => 'Sin PDF',
                'synopsis' => 'No debería publicarse.',
                'language' => 'Español',
                'status' => Book::STATUS_PUBLISHED,
            ])
            ->assertRedirect(route('panel.libros.create'))
            ->assertSessionHasErrors('pdf');

        $this->assertDatabaseMissing('books', ['title' => 'Sin PDF']);
    }

    public function test_editing_book_replaces_files(): void
    {
        $this->fakeBookStorage();

        $author = User::factory()->author()->create();
        $book = Book::factory()->for($author, 'author')->create();

        $oldCover = app(BookFiles::class)->storeCover(UploadedFile::fake()->image('old.jpg'));
        $oldPdf = app(BookFiles::class)->storePdf(UploadedFile::fake()->create('old.pdf', 120, 'application/pdf'));

        $book->update([
            'cover_path' => $oldCover,
            'pdf_path' => $oldPdf['pdf_path'],
            'pdf_original_name' => $oldPdf['pdf_original_name'],
            'pdf_size' => $oldPdf['pdf_size'],
        ]);
        $oldSlug = $book->slug;

        $this->actingAs($author)->put(route('panel.libros.update', $book), [
            'title' => 'Título actualizado',
            'synopsis' => 'Nueva sinopsis.',
            'language' => 'Español',
            'pages' => 222,
            'status' => Book::STATUS_PUBLISHED,
            'cover' => UploadedFile::fake()->image('new.jpg'),
            'pdf' => UploadedFile::fake()->create('new.pdf', 220, 'application/pdf'),
        ])->assertRedirect(route('panel.libros.edit', $book));

        $book->refresh();

        $this->assertSame('Título actualizado', $book->title);
        $this->assertSame($oldSlug, $book->slug);
        Storage::disk('local')->assertMissing($oldPdf['pdf_path']);
        Storage::disk('local')->assertExists($book->pdf_path);
        $this->assertFileDoesNotExist($this->coverAbsolutePath($oldCover));
        $this->assertFileExists($this->coverAbsolutePath($book->cover_path));
    }

    public function test_deleting_book_removes_files_and_saved_relations(): void
    {
        $this->fakeBookStorage();

        $author = User::factory()->author()->create();
        $reader = User::factory()->customer()->create();
        $book = Book::factory()->for($author, 'author')->create();

        $cover = app(BookFiles::class)->storeCover(UploadedFile::fake()->image('cover.jpg'));
        $pdf = app(BookFiles::class)->storePdf(UploadedFile::fake()->create('book.pdf', 180, 'application/pdf'));

        $book->update([
            'cover_path' => $cover,
            'pdf_path' => $pdf['pdf_path'],
            'pdf_original_name' => $pdf['pdf_original_name'],
            'pdf_size' => $pdf['pdf_size'],
        ]);
        $reader->library()->attach($book->id);

        $this->actingAs($author)
            ->delete(route('panel.libros.destroy', $book))
            ->assertRedirect(route('panel.libros.index'));

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseMissing('book_user', ['user_id' => $reader->id, 'book_id' => $book->id]);
        Storage::disk('local')->assertMissing($pdf['pdf_path']);
        $this->assertFileDoesNotExist($this->coverAbsolutePath($cover));
    }

    public function test_admin_can_create_book_for_another_active_author_and_toggle_featured(): void
    {
        $this->fakeBookStorage();

        $admin = User::factory()->admin()->create();
        $author = User::factory()->author()->create();

        $this->actingAs($admin)->post(route('panel.libros.store'), [
            'title' => 'Libro delegado',
            'synopsis' => 'Creado por administración.',
            'language' => 'Español',
            'status' => Book::STATUS_PUBLISHED,
            'author_id' => $author->id,
            'cover' => UploadedFile::fake()->image('cover.jpg'),
            'pdf' => UploadedFile::fake()->create('libro.pdf', 210, 'application/pdf'),
        ])->assertRedirect();

        $book = Book::query()->where('title', 'Libro delegado')->firstOrFail();
        $this->assertSame($author->id, $book->user_id);

        $this->actingAs($admin)
            ->patch(route('panel.libros.destacar', $book))
            ->assertRedirect();

        $this->assertTrue($book->fresh()->is_featured);
    }

    public function test_book_index_filters_results_and_shows_activity_columns(): void
    {
        $admin = User::factory()->admin()->create();
        $reader = User::factory()->customer()->create();
        $authorA = User::factory()->author()->create(['name' => 'Autora A']);
        $authorB = User::factory()->author()->create(['name' => 'Autor B']);
        $genreA = Genre::factory()->create(['name' => 'Fantasía']);
        $genreB = Genre::factory()->create(['name' => 'Historia']);

        $matching = Book::factory()->for($authorA, 'author')->for($genreA)->create([
            'title' => 'Crónicas del Lago',
            'status' => Book::STATUS_PUBLISHED,
        ]);
        BookRead::record($matching, $reader, BookRead::TYPE_READ);
        BookRead::record($matching, $reader, BookRead::TYPE_DOWNLOAD);
        $reader->library()->attach($matching->id);

        Book::factory()->for($authorB, 'author')->for($genreB)->draft()->create([
            'title' => 'Bitácora del Sur',
        ]);

        $this->actingAs($admin)
            ->get(route('panel.libros.index', [
                'q' => 'Crónicas',
                'estado' => 'published',
                'genero' => $genreA->id,
                'autor' => $authorA->id,
            ]))
            ->assertOk()
            ->assertSee('Crónicas del Lago')
            ->assertSee('Lecturas')
            ->assertSee('Descargas')
            ->assertSee('Guardados')
            ->assertDontSee('Bitácora del Sur');
    }

    private function fakeBookStorage(): void
    {
        Storage::fake('local');
        $this->coversPath = sys_get_temp_dir().'/wl-covers-'.uniqid();
        config(['wonderland.covers_path' => $this->coversPath]);
    }

    private function coverAbsolutePath(string $relativePath): string
    {
        return $this->coversPath.DIRECTORY_SEPARATOR.basename($relativePath);
    }
}
