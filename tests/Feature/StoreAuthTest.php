<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_normalizes_email_to_lowercase_and_logs_in(): void
    {
        $response = $this->post(route('registro.store'), [
            'name' => 'Lector Demo',
            'email' => 'LECTOR@Example.COM',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_CUSTOMER,
        ]);

        $response->assertRedirect(route('inicio'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'lector@example.com',
            'role' => User::ROLE_CUSTOMER,
        ]);
    }

    public function test_registration_can_create_author_account(): void
    {
        $response = $this->post(route('registro.store'), [
            'name' => 'Autora Demo',
            'email' => 'AUTORA@Example.COM',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_AUTHOR,
        ]);

        $response->assertRedirect(route('panel.dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'autora@example.com',
            'role' => User::ROLE_AUTHOR,
        ]);
    }

    public function test_registration_rejects_admin_role(): void
    {
        $response = $this->from(route('registro'))->post(route('registro.store'), [
            'name' => 'Intruso',
            'email' => 'INTRUSO@Example.COM',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_ADMIN,
        ]);

        $response->assertRedirect(route('registro'));
        $response->assertSessionHasErrors('role');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'intruso@example.com']);
    }

    public function test_login_redirects_users_by_role_and_accepts_uppercase_email(): void
    {
        $customer = User::factory()->customer()->create(['email' => 'cliente@example.com']);
        $author = User::factory()->author()->create(['email' => 'autor@example.com']);
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->post(route('login.store'), [
            'email' => 'CLIENTE@EXAMPLE.COM',
            'password' => 'password',
        ])->assertRedirect(route('inicio'));

        $this->assertAuthenticatedAs($customer);
        $this->post(route('logout'))->assertRedirect(route('inicio'));

        $this->post(route('login.store'), [
            'email' => 'AUTOR@EXAMPLE.COM',
            'password' => 'password',
        ])->assertRedirect(route('panel.dashboard'));

        $this->assertAuthenticatedAs($author);
        $this->post(route('logout'))->assertRedirect(route('inicio'));

        $this->post(route('login.store'), [
            'email' => 'ADMIN@EXAMPLE.COM',
            'password' => 'password',
        ])->assertRedirect(route('panel.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create(['email' => 'apagada@example.com']);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'APAGADA@EXAMPLE.COM',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_account_is_not_revealed_with_wrong_password(): void
    {
        $user = User::factory()->inactive()->create(['email' => 'apagada2@example.com']);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => strtoupper($user->email),
            'password' => 'incorrecta',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);

        $this->assertGuest();
    }
}
