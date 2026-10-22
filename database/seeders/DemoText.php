<?php

namespace Database\Seeders;

/**
 * Texto de relleno para los PDF de demostracion (oraciones originales por tipo de libro).
 */
class DemoText
{
    private const NARRATIVE = [
        'El viento movía las cortinas como si alguien acabara de salir de la habitación.',
        'Nadie se atrevió a preguntar en voz alta lo que todos estaban pensando.',
        'Durante un momento, el silencio pesó más que cualquier palabra.',
        'Recordó entonces la promesa que había hecho años atrás, sin imaginar que tendría que cumplirla.',
        'Afuera, la noche se había vuelto espesa y las luces del pueblo parecían muy lejanas.',
        'Tomó la carta con cuidado, como quien sostiene algo que puede romperse al leerlo.',
        'Cada paso sobre la escalera de madera sonaba más fuerte que el anterior.',
        'Sonrió sin ganas, porque entendía que aquella respuesta solo abría una pregunta mayor.',
        'Al amanecer, todo parecía igual; sin embargo, nada volvería a ser lo mismo.',
        'Guardó el secreto en el bolsillo del abrigo y salió a caminar bajo la lluvia fina.',
    ];

    private const NONFICTION = [
        'Lo importante no es hacerlo perfecto, sino empezar y mejorar con cada intento.',
        'Conviene dividir el problema en partes pequeñas y resolverlas una por una.',
        'Un buen ejemplo vale más que una página entera de explicaciones abstractas.',
        'Anota lo que aprendes: escribirlo ayuda a recordarlo y a detectar lo que aún no entiendes.',
        'La práctica constante supera al talento cuando se trata de adquirir una habilidad nueva.',
        'Revisa tu avance cada semana y celebra los progresos, por pequeños que parezcan.',
        'Si algo no funciona, cambia una sola variable y vuelve a probar.',
        'La claridad nace de explicar las ideas con palabras sencillas y ejemplos cercanos.',
        'Comparte lo que sabes con otras personas: enseñar es la mejor manera de aprender.',
        'Recuerda que cada experto fue alguna vez un principiante con muchas preguntas.',
    ];

    private const NARRATIVE_GENRES = ['Fantasía', 'Ciencia ficción', 'Misterio', 'Romance', 'Infantil'];

    /**
     * Genera $count parrafos de relleno de forma determinista (mismo titulo = mismo texto).
     *
     * @return list<string>
     */
    public static function paragraphs(string $genre, string $seedText, int $count = 6): array
    {
        $pool = in_array($genre, self::NARRATIVE_GENRES, true) ? self::NARRATIVE : self::NONFICTION;
        mt_srand(crc32($seedText));

        $paragraphs = [];
        for ($i = 0; $i < $count; $i++) {
            $sentences = [];
            for ($j = 0; $j < 4; $j++) {
                $sentences[] = $pool[mt_rand(0, count($pool) - 1)];
            }
            $paragraphs[] = implode(' ', $sentences);
        }

        mt_srand();

        return $paragraphs;
    }
}
