<?php

namespace App\Support;

/**
 * Generador de PDF minimo (sin librerias externas).
 *
 * Se usa para crear los libros de demostracion (seeders) y las pruebas automaticas.
 * Emplea las fuentes estandar de PDF (Times y Helvetica) con codificacion WinAnsi,
 * asi que los acentos y la "n" se ven bien en cualquier visor.
 */
class SimplePdf
{
    private const W = 595.28; // A4

    private const H = 841.89;

    /** Colores de portada (RGB 0-1). Se elige uno segun el titulo. */
    private const COVER_COLORS = [
        [0.12, 0.19, 0.30],
        [0.20, 0.25, 0.33],
        [0.09, 0.26, 0.21],
        [0.33, 0.11, 0.17],
        [0.18, 0.19, 0.21],
        [0.07, 0.28, 0.33],
        [0.15, 0.17, 0.36],
        [0.27, 0.24, 0.21],
    ];
    /** Anchos Times-Roman (x1000) para los caracteres ASCII 32..126. */
    private const TIMES_WIDTHS = [
        250, 333, 408, 500, 500, 833, 778, 180, 333, 333, 500, 564, 250, 333, 250, 278,
        500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 278, 278, 564, 564, 564, 444,
        921, 722, 667, 667, 722, 611, 556, 722, 722, 333, 389, 722, 611, 889, 722, 722,
        556, 722, 667, 556, 611, 722, 722, 944, 722, 722, 611, 333, 278, 333, 469, 500,
        333, 444, 500, 444, 500, 444, 333, 500, 500, 278, 278, 500, 278, 778, 500, 500,
        500, 500, 333, 389, 278, 500, 500, 722, 500, 500, 444, 480, 200, 480, 541,
    ];

    private const FONTS = ['F1' => 'Times-Roman', 'F2' => 'Times-Bold', 'F3' => 'Times-Italic', 'F4' => 'Helvetica', 'F5' => 'Helvetica-Bold'];

    /** @var list<string> */
    private array $pages = [];

    private string $title = '';

    private string $author = '';

    /**
     * Crea un PDF de libro: portada, aviso de demostracion y el contenido en paginas.
     * Un parrafo que empieza con "# " se imprime como subtitulo.
     *
     * @param  list<string>  $paragraphs
     */
    public static function book(string $title, string $author, string $genre, array $paragraphs, string $chapterTitle = 'Capítulo 1'): string
    {
        $pdf = new self;
        $pdf->title = $title;
        $pdf->author = $author;

        $color = self::COVER_COLORS[crc32($title) % count(self::COVER_COLORS)];

        $pdf->pages[] = $pdf->coverPage($title, $author, $genre, $color);
        $pdf->pages[] = $pdf->noticePage($title);

        foreach ($pdf->flowPages($paragraphs, $chapterTitle, 3) as $content) {
            $pdf->pages[] = $content;
        }

        return $pdf->render();
    }

    // ------------------------------------------------------------------
    // Paginas
    // ------------------------------------------------------------------

    private function coverPage(string $title, string $author, string $genre, array $rgb): string
    {
        [$r, $g, $b] = $rgb;
        $c = sprintf("%.3F %.3F %.3F rg 0 0 %.2F %.2F re f\n", $r, $g, $b, self::W, self::H);
        // Marco fino y filete sobrio
        $c .= "0.78 0.83 0.91 RG 0.8 w 40 40 515.28 761.89 re S\n";
        $c .= "0.78 0.83 0.91 RG 0.6 w 207 690 m 388 690 l S\n";

        $y = 580;
        foreach ($this->wrap(mb_strtoupper($title), 400, 30, 'F2') as $line) {
            $c .= $this->centered($line, $y, 'F2', 30, [1, 1, 1]);
            $y -= 40;
        }

        $c .= $this->centered('por', $y - 14, 'F3', 13, [0.80, 0.85, 0.92]);
        $c .= $this->centered($author, $y - 40, 'F3', 20, [0.92, 0.94, 0.97]);
        $c .= $this->centered(mb_strtoupper($genre), 130, 'F5', 10, [1, 1, 1]);
        $c .= $this->centered('LIBRARY OF WONDERLAND', 70, 'F5', 8, [0.80, 0.85, 0.92]);

        return $c;
    }

    private function noticePage(string $title): string
    {
        $c = $this->text(72, self::H - 110, 'Ejemplar de demostración', 'F2', 22);
        $y = self::H - 150;

        $notice = [
            "Este PDF de «{$title}» fue generado automáticamente por Library of Wonderland para probar el lector en línea, la descarga y la biblioteca personal.",
            'Los autores reales suben aquí su propio PDF desde el panel de autor. El archivo se guarda en el servidor y, mientras el libro esté publicado, cualquier persona puede leerlo y descargarlo gratis.',
            'Gracias por probar la plataforma.',
        ];

        foreach ($notice as $paragraph) {
            foreach ($this->wrap($paragraph, self::W - 144, 12, 'F1') as $line) {
                $c .= $this->text(72, $y, $line, 'F1', 12);
                $y -= 17;
            }
            $y -= 10;
        }

        return $c.$this->footer(2);
    }

    /**
     * @param  list<string>  $paragraphs
     * @return list<string>
     */
    private function flowPages(array $paragraphs, string $chapterTitle, int $firstNumber): array
    {
        $left = 72.0;
        $maxWidth = self::W - 144;
        $size = 12.0;
        $lead = 17.0;
        $bottom = 84.0;

        $pages = [];
        $number = $firstNumber;
        $content = $this->text($left, self::H - 100, $chapterTitle, 'F2', 22);
        $y = self::H - 146;

        foreach ($paragraphs as $paragraph) {
            $isHeading = str_starts_with($paragraph, '# ');
            $text = $isHeading ? substr($paragraph, 2) : $paragraph;
            $isVerse = ! $isHeading && str_contains($text, "\n");
            $font = $isHeading ? 'F2' : ($isVerse ? 'F3' : 'F1');
            $fontSize = $isHeading ? 15.0 : $size;

            if ($isHeading) {
                $y -= 10;
            }

            foreach ($isVerse ? explode("\n", $text) : [$text] as $segment) {
                foreach ($this->wrap($segment, $maxWidth, $fontSize, $font) as $line) {
                    if ($y < $bottom) {
                        $pages[] = $content.$this->footer($number++);
                        $content = '';
                        $y = self::H - 90;
                    }
                    $content .= $this->text($left, $y, $line, $font, $fontSize);
                    $y -= $isHeading ? $lead + 3 : $lead;
                }
            }

            $y -= $isHeading ? 2 : 9;
        }

        $pages[] = $content.$this->footer($number);

        return $pages;
    }

    private function footer(int $number): string
    {
        return $this->centered("— {$number} —", 46, 'F4', 9, [0.45, 0.45, 0.45]);
    }

    // ------------------------------------------------------------------
    // Texto
    // ------------------------------------------------------------------

    /** @param  array{0: float|int, 1: float|int, 2: float|int}  $rgb */
    private function text(float $x, float $y, string $text, string $font, float $size, array $rgb = [0.1, 0.1, 0.12]): string
    {
        return sprintf(
            "BT /%s %.2F Tf %.3F %.3F %.3F rg 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
            $font, $size, $rgb[0], $rgb[1], $rgb[2], $x, $y, $this->escape($text)
        );
    }

    /** @param  array{0: float|int, 1: float|int, 2: float|int}  $rgb */
    private function centered(string $text, float $y, string $font, float $size, array $rgb): string
    {
        $x = max(20, (self::W - $this->width($text, $size, $font)) / 2);

        return $this->text($x, $y, $text, $font, $size, $rgb);
    }

    /** @return list<string> */
    private function wrap(string $text, float $maxWidth, float $size, string $font): array
    {
        $lines = [];
        $line = '';

        foreach (preg_split('/\s+/u', trim($text)) ?: [] as $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;

            if ($line !== '' && $this->width($candidate, $size, $font) > $maxWidth) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    private function width(string $text, float $size, string $font): float
    {
        $plain = strtr($text, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
            '¿' => '?', '¡' => '!', '«' => '"', '»' => '"', '—' => '-', '–' => '-', '“' => '"', '”' => '"',
            '’' => "'", '‘' => "'", '…' => '.',
        ]);

        $units = 0;
        foreach (str_split($plain) as $char) {
            $code = ord($char);
            $units += ($code >= 32 && $code <= 126) ? self::TIMES_WIDTHS[$code - 32] : 500;
        }

        $factor = match ($font) {
            'F2' => 1.06,
            'F4' => 1.1,
            'F5' => 1.17,
            default => 1.0,
        };

        return $units / 1000 * $size * $factor;
    }

    private function escape(string $text): string
    {
        $cp1252 = mb_convert_encoding(str_replace(["\r", "\n"], ' ', $text), 'Windows-1252', 'UTF-8');

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $cp1252);
    }

    private function hexString(string $text): string
    {
        return '<FEFF'.strtoupper(bin2hex(mb_convert_encoding($text, 'UTF-16BE', 'UTF-8'))).'>';
    }

    // ------------------------------------------------------------------
    // Ensamblado del archivo
    // ------------------------------------------------------------------

    private function render(): string
    {
        $count = count($this->pages);
        $firstPage = 9;
        $kids = [];

        for ($i = 0; $i < $count; $i++) {
            $kids[] = ($firstPage + 2 * $i).' 0 R';
        }

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.$count.' >>',
        ];

        $slot = 3;
        $fontResources = [];
        foreach (self::FONTS as $alias => $baseFont) {
            $objects[$slot] = "<< /Type /Font /Subtype /Type1 /BaseFont /{$baseFont} /Encoding /WinAnsiEncoding >>";
            $fontResources[] = "/{$alias} {$slot} 0 R";
            $slot++;
        }

        $objects[8] = '<< /Title '.$this->hexString($this->title).' /Author '.$this->hexString($this->author)
            .' /Producer (Library of Wonderland) /Creator (Library of Wonderland) >>';

        foreach ($this->pages as $i => $stream) {
            $pageObject = $firstPage + 2 * $i;
            $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.self::W.' '.self::H.'] '
                .'/Resources << /Font << '.implode(' ', $fontResources).' >> >> /Contents '.($pageObject + 1).' 0 R >>';
            $objects[$pageObject + 1] = '<< /Length '.strlen($stream)." >>\nstream\n".$stream.'endstream';
        }

        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$body}\nendobj\n";
        }

        $xref = strlen($pdf);
        $total = count($objects) + 1;
        $pdf .= "xref\n0 {$total}\n0000000000 65535 f \n";

        for ($n = 1; $n < $total; $n++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$n]);
        }

        return $pdf."trailer\n<< /Size {$total} /Root 1 0 R /Info 8 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
