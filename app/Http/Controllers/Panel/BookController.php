<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use App\Services\BookFiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookController extends Controller
{
    public function __construct(private readonly BookFiles $files) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $filters = $request->only(['q', 'estado', 'genero', 'autor']);

        $books = Book::query()
            ->with(['author:id,name,is_active', 'genre:id,name,slug'])
            ->withRating()
            ->withStats()
            ->when(! $user->isAdmin(), fn (Builder $query) => $query->where('user_id', $user->id))
            ->search($filters['q'] ?? null)
            ->when(in_array($filters['estado'] ?? null, [Book::STATUS_DRAFT, Book::STATUS_PUBLISHED], true), fn (Builder $query) => $query->where('status', $filters['estado']))
            ->when(filled($filters['genero'] ?? null), fn (Builder $query) => $query->where('genre_id', $filters['genero']))
            ->when($user->isAdmin() && filled($filters['autor'] ?? null), fn (Builder $query) => $query->where('user_id', $filters['autor']))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('panel.books.index', [
            'books' => $books,
            'filters' => $filters,
            'genres' => Genre::query()->orderBy('name')->get(['id', 'name']),
            'authors' => $user->isAdmin()
                ? User::query()
                    ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_AUTHOR])
                    ->orderBy('name')
                    ->get(['id', 'name', 'is_active'])
                : collect(),
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function create(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $book = new Book([
            'language' => 'Español',
            'status' => Book::STATUS_DRAFT,
        ]);
        $book->setRelation('author', $user);

        return view('panel.books.form', [
            'book' => $book,
            'isEdit' => false,
            'isAdmin' => $user->isAdmin(),
            'genres' => Genre::query()->orderBy('name')->get(['id', 'name']),
            'authors' => $this->assignableAuthors(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateBook($request, creating: true);
        $author = $this->resolveAuthor($request, $validated);

        if (($validated['status'] ?? Book::STATUS_DRAFT) === Book::STATUS_PUBLISHED && ! $request->hasFile('pdf')) {
            throw ValidationException::withMessages([
                'pdf' => 'Debes subir un archivo PDF antes de publicar el libro.',
            ]);
        }

        $data = Arr::except($validated, ['cover', 'pdf', 'author_id']);
        $data['slug'] = Book::uniqueSlug($data['title']);
        $data['published_at'] = $data['status'] === Book::STATUS_PUBLISHED ? now() : null;

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $this->files->storeCover($request->file('cover'));
        }

        if ($request->hasFile('pdf')) {
            $data = [...$data, ...$this->files->storePdf($request->file('pdf'))];
        }

        $book = $author->books()->create($data);

        return redirect()
            ->route('panel.libros.edit', $book)
            ->with('success', 'Libro creado correctamente.');
    }

    public function edit(Request $request, Book $book): View
    {
        $this->authorize('manage', $book);

        return view('panel.books.form', [
            'book' => $book->loadMissing(['author:id,name,is_active', 'genre:id,name']),
            'isEdit' => true,
            'isAdmin' => $request->user()->isAdmin(),
            'genres' => Genre::query()->orderBy('name')->get(['id', 'name']),
            'authors' => collect(),
        ]);
    }

    public function update(Request $request, Book $book): RedirectResponse
    {
        $this->authorize('manage', $book);

        $validated = $this->validateBook($request, creating: false);

        if (($validated['status'] ?? $book->status) === Book::STATUS_PUBLISHED && ! $book->hasPdf() && ! $request->hasFile('pdf')) {
            throw ValidationException::withMessages([
                'pdf' => 'Debes subir un archivo PDF antes de publicar el libro.',
            ]);
        }

        $data = Arr::except($validated, ['cover', 'pdf', 'author_id']);
        $data['published_at'] = $data['status'] === Book::STATUS_PUBLISHED
            ? ($book->published_at ?? now())
            : null;

        $oldCover = null;
        $oldPdf = null;

        if ($request->hasFile('cover')) {
            $oldCover = $book->cover_path;
            $data['cover_path'] = $this->files->storeCover($request->file('cover'));
        }

        if ($request->hasFile('pdf')) {
            $oldPdf = $book->pdf_path;
            $data = [...$data, ...$this->files->storePdf($request->file('pdf'))];
        }

        $book->fill($data)->save();

        if ($oldCover) {
            $this->files->deleteCover($oldCover);
        }

        if ($oldPdf) {
            $this->files->deletePdf($oldPdf);
        }

        return redirect()
            ->route('panel.libros.edit', $book)
            ->with('success', 'Libro actualizado correctamente.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('manage', $book);

        $this->files->deleteCover($book->cover_path);
        $this->files->deletePdf($book->pdf_path);
        $book->delete();

        return redirect()
            ->route('panel.libros.index')
            ->with('warning', 'Se borró el libro, su PDF y su portada, y se quitó de las bibliotecas de los lectores.');
    }

    public function togglePublish(Book $book): RedirectResponse
    {
        $this->authorize('manage', $book);

        if ($book->isPublished()) {
            $book->status = Book::STATUS_DRAFT;
            $book->published_at = null;
            $book->save();

            return back()->with('info', 'El libro quedó oculto y volvió a borrador.');
        }

        if (! $book->hasPdf()) {
            return back()->with('error', 'No puedes publicar un libro sin PDF.');
        }

        $book->status = Book::STATUS_PUBLISHED;
        $book->published_at = $book->published_at ?? now();
        $book->save();

        return back()->with('success', 'Libro publicado correctamente.');
    }

    public function toggleFeatured(Request $request, Book $book): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $book->is_featured = ! $book->is_featured;
        $book->save();

        return back()->with(
            'success',
            $book->is_featured ? 'El libro quedó marcado como destacado.' : 'El libro dejó de estar destacado.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validateBook(Request $request, bool $creating): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'synopsis' => ['required', 'string'],
            'genre_id' => ['nullable', 'exists:genres,id'],
            'language' => ['required', 'string', 'max:40'],
            'pages' => ['nullable', 'integer', 'min:1', 'max:20000'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('wonderland.max_cover_kb')],
            'pdf' => array_merge(
                $creating ? ['required'] : ['nullable'],
                ['file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:'.config('wonderland.max_pdf_kb')]
            ),
            'status' => ['required', Rule::in([Book::STATUS_DRAFT, Book::STATUS_PUBLISHED])],
            'author_id' => [
                $request->user()->isAdmin() ? 'nullable' : 'prohibited',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_AUTHOR])
                    ->where('is_active', true)),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveAuthor(Request $request, array $validated): User
    {
        if (! $request->user()->isAdmin() || blank($validated['author_id'] ?? null)) {
            return $request->user();
        }

        return User::query()
            ->where('is_active', true)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_AUTHOR])
            ->findOrFail($validated['author_id']);
    }

    private function assignableAuthors()
    {
        return User::query()
            ->where('is_active', true)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_AUTHOR])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }
}
