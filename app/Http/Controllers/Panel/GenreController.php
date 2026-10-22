<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GenreController extends Controller
{
    public function index(): View
    {
        return view('panel.genres', [
            'genres' => Genre::query()->withCount('books')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:genres,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        Genre::query()->create([
            'name' => $validated['name'],
            'slug' => Genre::uniqueSlug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Género creado correctamente.');
    }

    public function update(Request $request, Genre $genre): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('genres', 'name')->ignore($genre->id)],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $genre->name = $validated['name'];
        $genre->description = $validated['description'] ?? null;

        if ($genre->isDirty('name')) {
            $genre->slug = Genre::uniqueSlug($validated['name'], $genre->id);
        }

        $genre->save();

        return back()->with('success', 'Género actualizado correctamente.');
    }

    public function destroy(Genre $genre): RedirectResponse
    {
        $booksCount = $genre->books()->count();
        $genre->delete();

        return back()->with(
            $booksCount > 0 ? 'warning' : 'success',
            $booksCount > 0
                ? 'Género eliminado. Los libros relacionados quedaron sin género.'
                : 'Género eliminado correctamente.'
        );
    }
}
