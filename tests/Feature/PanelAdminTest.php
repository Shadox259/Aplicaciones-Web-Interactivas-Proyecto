<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user_and_email_is_normalized_to_lowercase(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('panel.usuarios.store'), [
                'name' => 'Nueva Autora',
                'email' => 'NUEVA.AUTORA@Example.COM',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => User::ROLE_AUTHOR,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'Nueva Autora',
            'email' => 'nueva.autora@example.com',
            'role' => User::ROLE_AUTHOR,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_user_role_reactivate_and_deactivate_without_deleting_row(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->author()->inactive()->create();
        $book = Book::factory()->for($author, 'author')->create(['title' => 'Guía persistente']);

        $this->actingAs($admin)
            ->patch(route('panel.usuarios.update', $author), [
                'role' => User::ROLE_AUTHOR,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $author->refresh();
        $this->assertTrue($author->is_active);

        $this->actingAs($admin)
            ->delete(route('panel.usuarios.destroy', $author))
            ->assertRedirect()
            ->assertSessionHas('success');

        $author->refresh();
        $this->assertFalse($author->is_active);
        $this->assertDatabaseHas('users', ['id' => $author->id, 'is_active' => false]);

        $this->assertFalse(Auth::attempt([
            'email' => $author->email,
            'password' => 'password',
            'is_active' => true,
        ]));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $author->id,
            'title' => 'Guía persistente',
        ]);
    }

    public function test_admin_cannot_change_or_deactivate_self_as_last_active_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('panel.usuarios.update', $admin), [
                'role' => User::ROLE_CUSTOMER,
                'is_active' => 0,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->delete(route('panel.usuarios.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHas('error');

        $admin->refresh();
        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertTrue($admin->is_active);
    }

    public function test_genres_crud_works_from_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('panel.generos.store'), [
            'name' => 'Ciencia ficción',
            'description' => 'Historias con tecnología y mundos futuros.',
        ])->assertRedirect();

        $genre = Genre::query()->where('name', 'Ciencia ficción')->firstOrFail();
        $this->assertSame('ciencia-ficcion', $genre->slug);

        $this->actingAs($admin)->put(route('panel.generos.update', $genre), [
            'name' => 'Ciencia ficción épica',
            'description' => 'Grandes sagas futuristas.',
        ])->assertRedirect();

        $genre->refresh();
        $this->assertSame('ciencia-ficcion-epica', $genre->slug);

        $book = Book::factory()->for($genre)->create();

        $this->actingAs($admin)
            ->delete(route('panel.generos.destroy', $genre))
            ->assertRedirect();

        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
        $this->assertNull($book->fresh()->genre_id);
    }
}
