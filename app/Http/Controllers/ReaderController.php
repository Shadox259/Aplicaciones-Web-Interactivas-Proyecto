<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookRead;
use App\Services\BookFiles;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReaderController extends Controller
{
    public function show(Book $book): View
    {
        abort_unless(Gate::allows('read', $book), 404);

        $book->load(['author:id,name', 'genre:id,name,slug'])->loadCount([
            'reads as reads_count' => fn ($query) => $query->where('type', BookRead::TYPE_READ),
            'reads as downloads_count' => fn ($query) => $query->where('type', BookRead::TYPE_DOWNLOAD),
            'savedBy as saves_count',
        ]);

        BookRead::record($book, auth()->user(), BookRead::TYPE_READ);

        return view('store.reader', [
            'book' => $book,
        ]);
    }

    public function pdf(Book $book, BookFiles $files): BinaryFileResponse
    {
        abort_unless(Gate::allows('read', $book), 404);

        $path = $files->pdfFullPath($book);
        abort_unless($path, 404);

        return $this->applyCacheHeaders(
            $book,
            response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.Str::slug($book->title).'.pdf"',
                'X-Content-Type-Options' => 'nosniff',
            ])
        );
    }

    public function download(Book $book, BookFiles $files): BinaryFileResponse
    {
        abort_unless(Gate::allows('read', $book), 404);

        $path = $files->pdfFullPath($book);
        abort_unless($path, 404);

        BookRead::record($book, auth()->user(), BookRead::TYPE_DOWNLOAD);

        return $this->applyCacheHeaders(
            $book,
            response()->download($path, Str::slug($book->title).'.pdf', [
                'Content-Type' => 'application/pdf',
                'X-Content-Type-Options' => 'nosniff',
            ])
        );
    }

    private function applyCacheHeaders(Book $book, BinaryFileResponse $response): BinaryFileResponse
    {
        if ($book->isPublished()) {
            $response->setPublic();
            $response->setMaxAge(3600);
            $response->setSharedMaxAge(3600);
        } else {
            $response->setPrivate();
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        return $response;
    }
}
