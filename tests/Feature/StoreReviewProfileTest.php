<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreReviewProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_any_authenticated_user_except_author_can_create_update_and_delete_reviews(): void
    {
        $author = User::factory()->author()->create();
        $reader = User::factory()->customer()->create();
        $otherReader = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->for($author, 'author')->create();

        $this->actingAs($reader)->post(route('resenas.store', $book), [
            'rating' => 5,
            'comment' => 'Comentario inicial.',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $reader->id,
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        $this->actingAs($reader)->post(route('resenas.store', $book), [
            'rating' => 4,
            'comment' => 'Comentario actualizado.',
        ])->assertSessionHas('success');

        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', [
            'user_id' => $reader->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => 'Comentario actualizado.',
        ]);

        $this->actingAs($author)->post(route('resenas.store', $book), [
            'rating' => 5,
            'comment' => 'No permitido.',
        ])->assertSessionHas('error');

        $review = Review::first();
        $this->actingAs($admin)->delete(route('resenas.destroy', $review))->assertSessionHas('success');
        $this->assertDatabaseCount('reviews', 0);

        $this->actingAs($otherReader)->post(route('resenas.store', $book), [
            'rating' => 3,
            'comment' => 'Nueva reseña.',
        ])->assertSessionHas('success');
    }

    public function test_profile_can_be_updated_and_password_changed(): void
    {
        $author = User::factory()->author()->create([
            'name' => 'Nombre Original',
            'bio' => 'Bio anterior',
        ]);

        $this->actingAs($author)->put(route('perfil.update'), [
            'name' => 'Nombre Nuevo',
            'bio' => 'Nueva biografía pública',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $author->id,
            'name' => 'Nombre Nuevo',
            'bio' => 'Nueva biografía pública',
        ]);

        $this->actingAs($author)->put(route('perfil.password'), [
            'current_password' => 'password',
            'password' => 'supersegura123',
            'password_confirmation' => 'supersegura123',
        ])->assertSessionHas('success');

        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'email' => strtoupper($author->email),
            'password' => 'supersegura123',
        ])->assertRedirect(route('panel.dashboard'));
    }

    public function test_customer_can_deactivate_account_without_deleting_row_and_cannot_log_in_afterwards(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)->delete(route('perfil.destroy'), [
            'password' => 'password',
        ]);

        $response->assertRedirect(route('inicio'));
        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'is_active' => false,
        ]);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => strtoupper($customer->email),
            'password' => 'password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_admin_cannot_deactivate_self_from_profile(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->from(route('perfil.edit'))->delete(route('perfil.destroy'), [
            'password' => 'password',
        ])->assertRedirect(route('perfil.edit'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_active' => true,
        ]);
    }
}
