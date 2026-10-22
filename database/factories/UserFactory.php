<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * Por defecto crea un CLIENTE activo con contrasena "password".
     * Estados: ->admin()  ->author()  ->customer()  ->inactive()
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_CUSTOMER,
            'bio' => null,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_ADMIN]);
    }

    public function author(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_AUTHOR,
            'bio' => fake()->paragraph(),
        ]);
    }

    public function customer(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_CUSTOMER]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
