<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookRead;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_panel(): void
    {
        $this->get(route('panel.dashboard'))->assertRedirect(route('login'));
        $this->get(route('panel.libros.index'))->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_panel(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('panel.dashboard'))
            ->assertForbidden();
    }

    public function test_author_can_access_author_panel_but_not_admin_sections(): void
    {
        $author = User::factory()->author()->create();
        $book = Book::factory()->for($author, 'author')->create();

        $this->actingAs($author)->get(route('panel.dashboard'))->assertOk();
        $this->actingAs($author)->get(route('panel.libros.index'))->assertOk();

        $this->actingAs($author)->get(route('panel.usuarios.index'))->assertForbidden();
        $this->actingAs($author)->get(route('panel.generos.index'))->assertForbidden();
        $this->actingAs($author)->patch(route('panel.libros.destacar', $book))->assertForbidden();
    }

    public function test_admin_can_access_all_panel_sections(): void
    {
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->create();

        $this->actingAs($admin)->get(route('panel.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('panel.libros.index'))->assertOk();
        $this->actingAs($admin)->get(route('panel.libros.create'))->assertOk();
        $this->actingAs($admin)->get(route('panel.usuarios.index'))->assertOk();
        $this->actingAs($admin)->get(route('panel.generos.index'))->assertOk();
        $this->actingAs($admin)->patch(route('panel.libros.destacar', $book))->assertRedirect();
    }

    public function test_admin_dashboard_renders_activity_metrics(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->author()->create();
        $reader = User::factory()->customer()->create();
        $book = Book::factory()->for($author, 'author')->create(['title' => 'Atlas de Archivo']);

        BookRead::record($book, $reader, BookRead::TYPE_READ);
        BookRead::record($book, $reader, BookRead::TYPE_DOWNLOAD);
        $reader->library()->attach($book->id);
        Review::query()->create([
            'user_id' => $reader->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'Excelente consulta.',
        ]);

        $this->actingAs($admin)
            ->get(route('panel.dashboard'))
            ->assertOk()
            ->assertSee('Lecturas de los últimos 7 días')
            ->assertSee('Libros más leídos')
            ->assertSee('Últimas reseñas')
            ->assertSee('Usuarios recientes')
            ->assertSee('Atlas de Archivo')
            ->assertSee('1 en los últimos 7 días');
    }

    public function test_author_dashboard_renders_only_own_activity(): void
    {
        $author = User::factory()->author()->create(['name' => 'Laura Vidal']);
        $otherAuthor = User::factory()->author()->create();
        $reader = User::factory()->customer()->create();
        $ownBook = Book::factory()->for($author, 'author')->create(['title' => 'Bosque Técnico']);
        $otherBook = Book::factory()->for($otherAuthor, 'author')->create(['title' => 'Manual Ajeno']);

        BookRead::record($ownBook, $reader, BookRead::TYPE_READ);
        BookRead::record($otherBook, $reader, BookRead::TYPE_READ);
        $reader->library()->attach($ownBook->id);
        Review::query()->create([
            'user_id' => $reader->id,
            'book_id' => $ownBook->id,
            'rating' => 4,
            'comment' => 'Muy útil.',
        ]);

        $this->actingAs($author)
            ->get(route('panel.dashboard'))
            ->assertOk()
            ->assertSee('Subir libro')
            ->assertSee('Últimas reseñas')
            ->assertSee('Bosque Técnico')
            ->assertDontSee('Manual Ajeno')
            ->assertSee('1 en los últimos 7 días');
    }
}
