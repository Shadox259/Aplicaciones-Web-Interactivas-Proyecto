<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Manejo de archivos de los libros.
 *
 *  - Portadas: carpeta publica  public/uploads/covers  (no requiere `storage:link`, que en
 *    Windows exige permisos de administrador).
 *  - PDFs: almacenamiento PRIVADO  storage/app/private/books  -> solo se entregan a traves de
 *    ReaderController, despues de comprobar que el usuario tiene derecho a leerlos.
 */
class BookFiles
{
    public const COVERS_URL_DIR = 'uploads/covers';

    public const PDF_DIR = 'books';

    public function coversPath(): string
    {
        return config('wonderland.covers_path') ?: public_path(self::COVERS_URL_DIR);
    }

    /** Guarda la portada y devuelve la ruta relativa a /public (se guarda en books.cover_path). */
    public function storeCover(UploadedFile $file): string
    {
        File::ensureDirectoryExists($this->coversPath());

        $extension = strtolower($file->guessExtension() ?: 'jpg');
        $name = Str::random(40).'.'.($extension === 'jpeg' ? 'jpg' : $extension);

        $file->move($this->coversPath(), $name);

        return self::COVERS_URL_DIR.'/'.$name;
    }

    /**
     * Guarda el PDF en el disco privado.
     *
     * @return array{pdf_path: string, pdf_original_name: string, pdf_size: int}
     */
    public function storePdf(UploadedFile $file): array
    {
        $size = (int) $file->getSize();
        $original = Str::limit(basename($file->getClientOriginalName()), 190, '');
        $path = Storage::disk('local')->putFileAs(self::PDF_DIR, $file, Str::uuid()->toString().'.pdf');

        return [
            'pdf_path' => $path,
            'pdf_original_name' => $original,
            'pdf_size' => $size,
        ];
    }

    /** Guarda bytes de un PDF generado (seeders y pruebas). Devuelve los mismos datos que storePdf(). */
    public function storePdfContents(string $contents, string $originalName): array
    {
        $path = self::PDF_DIR.'/'.Str::uuid()->toString().'.pdf';
        Storage::disk('local')->put($path, $contents);

        return [
            'pdf_path' => $path,
            'pdf_original_name' => $originalName,
            'pdf_size' => strlen($contents),
        ];
    }

    public function deleteCover(?string $path): void
    {
        if (! $path || ! str_starts_with($path, self::COVERS_URL_DIR.'/')) {
            return;
        }

        // basename(): evita rutas del tipo ../../.env
        $file = $this->coversPath().DIRECTORY_SEPARATOR.basename($path);

        if (File::isFile($file)) {
            File::delete($file);
        }
    }

    public function deletePdf(?string $path): void
    {
        if ($path && str_starts_with($path, self::PDF_DIR.'/')) {
            Storage::disk('local')->delete($path);
        }
    }

    /** Ruta absoluta del PDF de un libro, o null si no existe. */
    public function pdfFullPath(Book $book): ?string
    {
        if (! $book->hasPdf() || ! str_starts_with($book->pdf_path, self::PDF_DIR.'/')) {
            return null;
        }

        $disk = Storage::disk('local');

        return $disk->exists($book->pdf_path) ? $disk->path($book->pdf_path) : null;
    }
}
