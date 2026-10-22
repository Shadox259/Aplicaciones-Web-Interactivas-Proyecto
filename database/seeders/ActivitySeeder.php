<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\BookRead;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Actividad de demostracion: libros guardados en "Mi biblioteca", resenas y lecturas/descargas
 * repartidas en los ultimos 14 dias (para que el panel tenga estadisticas y graficas).
 */
class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        // Solo una vez: si ya hay actividad no se duplica.
        if (BookRead::query()->exists() || Review::query()->exists()) {
            return;
        }

        $users = User::all()->keyBy('email');
        $books = Book::published()->get()->keyBy('title');

        $this->saves($users, $books);
        $this->reviews($users, $books);
        $this->reads($users, $books);
    }

    private function saves($users, $books): void
    {
        $saves = [
            'cliente@wonderland.test' => ['Protocolo Andrómeda', 'Cartas bajo la Lluvia', 'Laravel desde Cero', 'Versos de Medianoche', 'El Dragón que Olvidó Volar'],
            'ana@wonderland.test' => ['El Jardín de los Relojes', 'Hábitos que Transforman', 'Crónicas del Reino de Cristal', 'El Dragón que Olvidó Volar'],
            'jorge@wonderland.test' => ['Sombras en el Campanario', 'Laravel desde Cero', 'Bases de Datos para Humanos'],
            'valeria@wonderland.test' => ['Cocina de Bolsillo: 30 recetas en 30 minutos', 'Cartas bajo la Lluvia', 'La Última Biblioteca'],
            'pedro@wonderland.test' => ['El Caso de la Calle Olvidada', 'Protocolo Andrómeda', 'Historias de la Ciudad de México'],
            'autor@wonderland.test' => ['Bases de Datos para Humanos', 'Cocina de Bolsillo: 30 recetas en 30 minutos'],
        ];

        $n = 0;
        foreach ($saves as $email => $titles) {
            foreach ($titles as $title) {
                $when = now()->subDays(1 + ($n++ % 12))->setTime(9 + $n % 11, ($n * 7) % 60);
                $users[$email]->library()->attach($books[$title]->id, ['created_at' => $when, 'updated_at' => $when]);
            }
        }
    }

    private function reviews($users, $books): void
    {
        $reviews = [
            ['cliente@wonderland.test', 'Protocolo Andrómeda', 5, 'Me atrapó desde la primera página; el final es brillante.'],
            ['cliente@wonderland.test', 'Cartas bajo la Lluvia', 4, 'Dulce y muy bien escrito, ideal para una tarde de lluvia.'],
            ['cliente@wonderland.test', 'Laravel desde Cero', 5, 'Explica todo paso a paso. Me sirvió para mi proyecto de la materia.'],
            ['cliente@wonderland.test', 'Versos de Medianoche', 5, 'Poemas breves y hermosos.'],
            ['ana@wonderland.test', 'El Jardín de los Relojes', 5, 'Una fantasía cálida; la rosa negra me dio escalofríos.'],
            ['ana@wonderland.test', 'Hábitos que Transforman', 4, 'Práctico y directo; ya empecé con el ejercicio de los dos minutos.'],
            ['ana@wonderland.test', 'Crónicas del Reino de Cristal', 4, 'Gran construcción de mundo, aunque esperaba un final más largo.'],
            ['jorge@wonderland.test', 'Laravel desde Cero', 5, 'El mejor recurso en español que he encontrado.'],
            ['jorge@wonderland.test', 'Bases de Datos para Humanos', 4, 'Claro y con buenos ejemplos.'],
            ['jorge@wonderland.test', 'Sombras en el Campanario', 3, 'Buen ambiente, pero el desenlace me pareció apresurado.'],
            ['valeria@wonderland.test', 'Cartas bajo la Lluvia', 5, 'Lloré y sonreí. Muy recomendable.'],
            ['valeria@wonderland.test', 'La Última Biblioteca', 5, 'Una idea preciosa y un final que duele.'],
            ['valeria@wonderland.test', 'Cocina de Bolsillo: 30 recetas en 30 minutos', 4, 'Las recetas de pasta son un éxito en casa.'],
            ['pedro@wonderland.test', 'El Caso de la Calle Olvidada', 4, 'Misterio bien armado con humor negro.'],
            ['pedro@wonderland.test', 'Protocolo Andrómeda', 5, 'Ciencia ficción de la buena.'],
            ['pedro@wonderland.test', 'Historias de la Ciudad de México', 4, 'Me hizo ver mi ciudad con otros ojos.'],
            ['autor@wonderland.test', 'Bases de Datos para Humanos', 4, 'Útil incluso para quienes ya programamos.'],
        ];

        foreach ($reviews as $i => [$email, $title, $rating, $comment]) {
            $when = now()->subDays(1 + $i % 10)->setTime(10 + $i % 9, ($i * 11) % 60);

            $review = new Review([
                'user_id' => $users[$email]->id,
                'book_id' => $books[$title]->id,
                'rating' => $rating,
                'comment' => $comment,
            ]);
            $review->created_at = $when;
            $review->updated_at = $when;
            $review->save();
        }
    }

    private function reads($users, $books): void
    {
        $readers = $users->filter(fn (User $u) => $u->isCustomer())->values();
        $rows = [];

        mt_srand(20260930);

        foreach ($books as $book) {
            $reads = 8 + (crc32($book->title) % 30);
            $downloads = intdiv($reads, 4);

            for ($i = 0; $i < $reads + $downloads; $i++) {
                $anonymous = mt_rand(1, 100) <= 40;

                $rows[] = [
                    'book_id' => $book->id,
                    'user_id' => $anonymous ? null : $readers[mt_rand(0, $readers->count() - 1)]->id,
                    'type' => $i < $reads ? BookRead::TYPE_READ : BookRead::TYPE_DOWNLOAD,
                    'created_at' => now()->subDays(mt_rand(0, 13))->setTime(mt_rand(8, 22), mt_rand(0, 59))->toDateTimeString(),
                ];
            }
        }

        mt_srand();

        foreach (array_chunk($rows, 100) as $chunk) {
            BookRead::query()->insert($chunk);
        }
    }
}
