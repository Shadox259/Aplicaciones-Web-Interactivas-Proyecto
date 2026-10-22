@extends('layouts.panel')

@section('title', $isAdmin ? 'Libros' : 'Mis libros')

@section('subtitle')
    @if ($isAdmin)
        Administra el catálogo completo, su visibilidad pública y los títulos destacados.
    @else
        Gestiona tus libros, actualiza sus archivos y controla su publicación.
    @endif
@endsection

@section('actions')
    <a href="{{ route('panel.libros.create') }}" class="btn btn-primary">
        <x-icon name="plus" class="h-5 w-5" />
        Subir libro
    </a>
@endsection

@section('content')
    <div class="space-y-6">
        <section class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('panel.libros.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div class="xl:col-span-2">
                        <label for="q" class="label">Buscar</label>
                        <input id="q" type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Título, sinopsis o autor">
                    </div>

                    <div>
                        <label for="estado" class="label">Estado</label>
                        <select id="estado" name="estado" class="input">
                            <option value="">Todos</option>
                            <option value="draft" @selected(($filters['estado'] ?? '') === 'draft')>Borrador</option>
                            <option value="published" @selected(($filters['estado'] ?? '') === 'published')>Publicado</option>
                        </select>
                    </div>

                    <div>
                        <label for="genero" class="label">Género</label>
                        <select id="genero" name="genero" class="input">
                            <option value="">Todos</option>
                            @foreach ($genres as $genre)
                                <option value="{{ $genre->id }}" @selected((string) ($filters['genero'] ?? '') === (string) $genre->id)>{{ $genre->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($isAdmin)
                        <div>
                            <label for="autor" class="label">Autor</label>
                            <select id="autor" name="autor" class="input">
                                <option value="">Todos</option>
                                @foreach ($authors as $author)
                                    <option value="{{ $author->id }}" @selected((string) ($filters['autor'] ?? '') === (string) $author->id)>
                                        {{ $author->name }}{{ $author->is_active ? '' : ' · inactivo' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="flex flex-wrap items-end gap-2 {{ $isAdmin ? 'xl:col-span-5' : 'md:col-span-2 xl:col-span-5' }}">
                        <button type="submit" class="btn btn-primary">
                            <x-icon name="funnel" class="h-5 w-5" />
                            Filtrar
                        </button>
                        <a href="{{ route('panel.libros.index') }}" class="btn btn-outline">Limpiar</a>
                    </div>
                </form>
            </div>
        </section>

        @if ($books->isEmpty())
            <section class="card">
                <div class="card-body py-14 text-center">
                    <x-icon name="book-open" class="mx-auto h-12 w-12 text-slate-300" />
                    <h2 class="mt-4 text-xl font-semibold text-slate-900">No hay libros para mostrar</h2>
                    <p class="mt-2 text-sm text-slate-500">
                        @if ($filters['q'] ?? $filters['estado'] ?? $filters['genero'] ?? $filters['autor'] ?? null)
                            Ajusta los filtros para encontrar otros resultados.
                        @elseif ($isAdmin)
                            Los libros creados por el equipo aparecerán aquí en cuanto se registren.
                        @else
                            Sube tu primer libro en PDF para comenzar a construir tu catálogo.
                        @endif
                    </p>
                    <div class="mt-5">
                        <a href="{{ route('panel.libros.create') }}" class="btn btn-primary">
                            <x-icon name="cloud-arrow-up" class="h-5 w-5" />
                            Subir libro
                        </a>
                    </div>
                </div>
            </section>
        @else
            <section class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Libro</th>
                        <th>Actividad</th>
                        <th>Estado</th>
                        <th>Calificación</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($books as $book)
                        <tr>
                            <td>
                                <div class="flex min-w-[260px] items-center gap-3">
                                    <a href="{{ route('libros.show', $book) }}" class="block w-11 shrink-0">
                                        <x-book-cover :book="$book" class="w-11" />
                                    </a>
                                    <div class="min-w-0">
                                        <a href="{{ route('libros.show', $book) }}" class="line-clamp-2 font-semibold text-slate-900 hover:text-brand-700">
                                            {{ $book->title }}
                                        </a>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            @if ($isAdmin)
                                                {{ $book->author?->name ?? 'Sin autor' }}@if ($book->author && ! $book->author->is_active) <span class="text-red-600">(cuenta desactivada)</span>@endif ·
                                            @endif
                                            {{ $book->genre?->name ?? 'Sin género' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-4 whitespace-nowrap text-sm text-slate-700">
                                    <span class="inline-flex items-center gap-1.5" title="Lecturas"><x-icon name="book-open" class="h-4 w-4 text-slate-400" /> {{ number_format($book->reads_total) }}</span>
                                    <span class="inline-flex items-center gap-1.5" title="Descargas"><x-icon name="arrow-down-tray" class="h-4 w-4 text-slate-400" /> {{ number_format($book->downloads_total) }}</span>
                                    <span class="inline-flex items-center gap-1.5" title="Guardados en bibliotecas"><x-icon name="bookmark" class="h-4 w-4 text-slate-400" /> {{ number_format($book->saves_total) }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="flex flex-col items-start gap-1">
                                    <span class="badge {{ $book->isPublished() ? 'badge-green' : 'badge-slate' }}">
                                        {{ $book->isPublished() ? 'Publicado' : 'Borrador' }}
                                    </span>
                                    @if ($book->hasPdf())
                                        <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                            <x-icon name="document-text" class="h-3.5 w-3.5" /> PDF {{ $book->pdf_size_label }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700">
                                            <x-icon name="exclamation-triangle" class="h-3.5 w-3.5" /> Sin PDF
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="whitespace-nowrap"><x-stars :value="$book->rating" :count="$book->reviews_total" /></td>
                            <td>
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('libros.show', $book) }}" class="btn btn-outline btn-sm btn-icon" title="Ver ficha pública" aria-label="Ver {{ $book->title }}"><x-icon name="eye" class="h-4 w-4" /></a>
                                    <a href="{{ route('panel.libros.edit', $book) }}" class="btn btn-outline btn-sm btn-icon" title="Editar" aria-label="Editar {{ $book->title }}"><x-icon name="pencil-square" class="h-4 w-4" /></a>

                                    <form method="POST" action="{{ route('panel.libros.publicar', $book) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline btn-sm btn-icon"
                                                title="{{ $book->isPublished() ? 'Ocultar (pasar a borrador)' : 'Publicar' }}"
                                                aria-label="{{ $book->isPublished() ? 'Ocultar' : 'Publicar' }} {{ $book->title }}">
                                            <x-icon :name="$book->isPublished() ? 'eye-slash' : 'cloud-arrow-up'" class="h-4 w-4" />
                                        </button>
                                    </form>

                                    @if ($isAdmin)
                                        <form method="POST" action="{{ route('panel.libros.destacar', $book) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline btn-sm btn-icon {{ $book->is_featured ? 'border-amber-300 text-amber-600' : '' }}"
                                                    title="{{ $book->is_featured ? 'Quitar de destacados' : 'Destacar en el inicio' }}"
                                                    aria-label="{{ $book->is_featured ? 'Quitar de destacados' : 'Destacar' }} {{ $book->title }}">
                                                <x-icon name="star" :solid="$book->is_featured" class="h-4 w-4" />
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('panel.libros.destroy', $book) }}"
                                          onsubmit="return confirm('Se borrará el libro, su PDF y portada, y se quitará de las bibliotecas de los lectores. ¿Deseas continuar?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger-outline btn-sm btn-icon" title="Eliminar libro" aria-label="Eliminar {{ $book->title }}"><x-icon name="trash" class="h-4 w-4" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </section>

            <div>
                {{ $books->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
