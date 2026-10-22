<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $genres = [
            'Fantasía' => 'Mundos imaginarios, magia y aventuras épicas.',
            'Ciencia ficción' => 'Tecnología, espacio y futuros posibles.',
            'Misterio' => 'Crímenes, secretos e intrigas por resolver.',
            'Romance' => 'Historias de amor, encuentros y desencuentros.',
            'Poesía' => 'Versos para leer despacio.',
            'Historia' => 'Crónicas y relatos del pasado.',
            'Tecnología' => 'Programación, bases de datos y cultura digital.',
            'Cocina' => 'Recetas, técnicas y sabores.',
            'Desarrollo personal' => 'Hábitos, productividad y bienestar.',
            'Infantil' => 'Cuentos para los más pequeños.',
        ];

        foreach ($genres as $name => $description) {
            Genre::firstOrCreate(
                ['name' => $name],
                ['slug' => Genre::uniqueSlug($name), 'description' => $description]
            );
        }
    }
}
