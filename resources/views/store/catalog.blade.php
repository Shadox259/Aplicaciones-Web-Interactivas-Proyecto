@extends('layouts.app')

@section('title', 'Catálogo')

@section('content')
    <div class="container-page py-10">
        <section class="card">
            <div class="card-body">
                <div class="flex flex-col gap-2">
                    <p class="text-xs uppercase tracking-wider text-slate-500">Catálogo</p>
                    <h1 class="text-2xl font-semibold text-slate-900">Libros disponibles</h1>
                    <p class="text-sm text-slate-600">Filtra por autor, género o popularidad para encontrar la lectura adecuada.</p>
                </div>

                <form action="{{ route('catalogo') }}" method="GET" class="mt-6 grid gap-4 lg:grid-cols-5">
                    <x-field name="q" label="Buscar" :value="$filters['q']" placeholder="Título, autor o sinopsis" class="lg:col-span-2" />

                    <x-field name="genero" label="Género" type="select">
                        <option value="">Todos los géneros</option>
                        @foreach ($genres as $genre)
                            <option value="{{ $genre->slug }}" @selected($filters['genero'] === $genre->slug)>{{ $genre->name }} ({{ $genre->books_count }})</option>
                        @endforeach
                    </x-field>

                    <x-field name="autor" label="Autor" type="select">
                        <option value="">Todos los autores</option>
                        @foreach ($authors as $author)
                            <option value="{{ $author->id }}" @selected($filters['autor'] === (string) $author->id)>{{ $author->name }} ({{ $author->published_books_count }})</option>
                        @endforeach
                    </x-field>

                    <x-field name="orden" label="Orden" type="select">
                        <option value="recientes" @selected($filters['orden'] === 'recientes')>Más recientes</option>
                        <option value="valorados" @selected($filters['orden'] === 'valorados')>Mejor valorados</option>
                        <option value="populares" @selected($filters['orden'] === 'populares')>Más leídos</option>
                        <option value="titulo" @selected($filters['orden'] === 'titulo')>Título</option>
                    </x-field>

                    <div class="flex flex-wrap items-end gap-3 lg:col-span-5">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="{{ route('catalogo') }}" class="btn btn-outline">Limpiar</a>
                    </div>
                </form>
            </div>
        </section>

        <section class="mt-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-medium text-slate-700">{{ $books->total() }} {{ \Illuminate\Support\Str::plural('resultado', $books->total()) }}</p>
                <p class="text-sm text-slate-500">Página {{ $books->currentPage() }} de {{ $books->lastPage() }}</p>
            </div>

            @if ($books->isNotEmpty())
                <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 sm:gap-6">
                    @foreach ($books as $book)
                        <x-book-card :book="$book" />
                    @endforeach
                </div>

                <div class="mt-8">{{ $books->withQueryString()->links() }}</div>
            @else
                <div class="card mt-6">
                    <div class="card-body py-14 text-center">
                        <x-icon name="information-circle" class="mx-auto h-12 w-12 text-slate-400" />
                        <h2 class="mt-4 text-xl font-semibold text-slate-900">No se encontraron libros con esos criterios</h2>
                        <p class="mt-3 text-sm text-slate-600">Prueba con otros filtros o restablece la búsqueda para revisar el catálogo completo.</p>
                        <div class="mt-6"><a href="{{ route('catalogo') }}" class="btn btn-primary">Ver todos los libros</a></div>
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
