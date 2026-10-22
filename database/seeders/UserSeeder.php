<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // Las 3 cuentas principales (ver config/wonderland.php -> demo_accounts)
            ['Administrador Wonderland', 'admin@wonderland.test', 'admin123', User::ROLE_ADMIN, null],
            ['Lucía Mendoza', 'autor@wonderland.test', 'autor123', User::ROLE_AUTHOR, 'Escritora de fantasía y ciencia ficción. Cree que un buen libro es una puerta que se abre desde adentro. Autora de "El Jardín de los Relojes".'],
            ['Carlos Ramírez', 'cliente@wonderland.test', 'cliente123', User::ROLE_CUSTOMER, null],

            // Mas autores
            ['Mateo Rivas', 'mateo@wonderland.test', 'autor123', User::ROLE_AUTHOR, 'Periodista convertido en novelista de misterio y suspenso. Le gustan los finales que nadie espera.'],
            ['Sofía Herrera', 'sofia@wonderland.test', 'autor123', User::ROLE_AUTHOR, 'Poeta, narradora y coach de hábitos. Escribe sobre las cosas pequeñas que cambian una vida.'],
            ['Daniel Ortega', 'daniel@wonderland.test', 'autor123', User::ROLE_AUTHOR, 'Ingeniero de software y divulgador. Explica tecnología, cocina e historia con ejemplos cotidianos.'],

            // Mas clientes
            ['Ana Torres', 'ana@wonderland.test', 'cliente123', User::ROLE_CUSTOMER, null],
            ['Jorge Salinas', 'jorge@wonderland.test', 'cliente123', User::ROLE_CUSTOMER, null],
            ['Valeria Cruz', 'valeria@wonderland.test', 'cliente123', User::ROLE_CUSTOMER, null],
            ['Pedro Núñez', 'pedro@wonderland.test', 'cliente123', User::ROLE_CUSTOMER, null],
        ];

        foreach ($users as $i => [$name, $email, $password, $role, $bio]) {
            $user = User::firstOrNew(['email' => $email]);
            $isNew = ! $user->exists;

            $user->name = $name;
            $user->role = $role;
            $user->bio = $bio;
            $user->is_active = true;
            $user->email_verified_at ??= now();

            if ($isNew) {
                $user->password = $password;
                $user->created_at = now()->subDays(30 - $i * 2);
            }

            $user->save();
        }
    }
}
