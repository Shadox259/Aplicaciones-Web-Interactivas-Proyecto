<?php

namespace Tests\Unit;

use App\Support\SimplePdf;
use PHPUnit\Framework\TestCase;

class SimplePdfTest extends TestCase
{
    public function test_generates_a_structurally_valid_pdf(): void
    {
        $pdf = SimplePdf::book(
            'Título con acentos: ñandú (prueba)',
            'Autora Ejemplo',
            'Fantasía',
            ['Un párrafo con «comillas» y paréntesis (abiertos) \\ y barras.', "Verso uno\nverso dos", '# Subtítulo', str_repeat('Texto largo para forzar varias páginas. ', 500)]
        );

        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringEndsWith("%%EOF\n", $pdf);

        // startxref apunta exactamente a la tabla xref
        $this->assertSame(1, preg_match('/startxref\n(\d+)\n%%EOF/', $pdf, $start));
        $this->assertSame('xref', substr($pdf, (int) $start[1], 4));

        // cada entrada de la tabla xref apunta a "N 0 obj"
        $this->assertSame(1, preg_match('/xref\n0 (\d+)\n/', $pdf, $head));
        $total = (int) $head[1];
        preg_match_all('/^(\d{10}) 00000 n $/m', $pdf, $offsets);
        $this->assertCount($total - 1, $offsets[1]);

        foreach ($offsets[1] as $i => $offset) {
            $this->assertStringStartsWith(($i + 1).' 0 obj', substr($pdf, (int) $offset, 12));
        }

        // portada + aviso + varias paginas de contenido
        $this->assertSame(1, preg_match('/\/Count (\d+)/', $pdf, $count));
        $this->assertGreaterThanOrEqual(5, (int) $count[1]);
    }

    public function test_declared_stream_lengths_match_real_lengths(): void
    {
        $pdf = SimplePdf::book('Libro', 'Autor', 'Demo', ['Hola mundo.']);

        preg_match_all('/<< \/Length (\d+) >>\nstream\n(.*?)endstream/s', $pdf, $streams, PREG_SET_ORDER);

        $this->assertNotEmpty($streams);
        foreach ($streams as [, $length, $body]) {
            $this->assertSame((int) $length, strlen($body));
        }
    }
}
