<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos de demostracion: usuarios de los 3 roles, generos, libros con PDF, biblioteca, resenas y actividad.
     * Es seguro ejecutarlo varias veces (no duplica usuarios, generos ni libros).
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            GenreSeeder::class,
            BookSeeder::class,
            ActivitySeeder::class,
        ]);
    }
}
