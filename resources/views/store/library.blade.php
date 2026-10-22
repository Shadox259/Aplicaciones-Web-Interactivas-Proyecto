@extends('layouts.app')

@section('title', 'Mi biblioteca')

@section('content')
    <div class="container-page py-10">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs uppercase tracking-wider text-slate-500">Biblioteca</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">Libros guardados</h1>
                <p class="mt-2 text-sm text-slate-600">Administra los títulos que deseas consultar más adelante o tener a mano.</p>
            </div>

            <form action="{{ route('biblioteca') }}" method="GET" class="w-full max-w-md">
                <div class="relative">
                    <x-icon name="magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                    <input type="search" name="q" value="{{ $query }}" placeholder="Buscar en mi biblioteca" class="input pl-10">
                </div>
            </form>
        </div>

        @if ($books->isNotEmpty())
            <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 sm:gap-6">
                @foreach ($books as $book)
                    <x-book-card :book="$book" />
                @endforeach
            </div>
            <div class="mt-8">{{ $books->withQueryString()->links() }}</div>
        @else
            <div class="card mt-8">
                <div class="card-body py-16 text-center">
                    <x-icon name="bookmark" class="mx-auto h-14 w-14 text-slate-400" />
                    <h2 class="mt-5 text-2xl font-semibold text-slate-900">Tu biblioteca está vacía</h2>
                    <p class="mt-3 text-sm text-slate-600">Guarda libros desde el catálogo para acceder a ellos rápidamente desde esta sección.</p>
                    <div class="mt-6"><a href="{{ route('catalogo') }}" class="btn btn-primary">Ir al catálogo</a></div>
                </div>
            </div>
        @endif

        @if (auth()->user()->canPublish())
            <section class="mt-14">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500">Autor</p>
                        <h2 class="mt-1 text-2xl font-semibold text-slate-900">Mis libros publicados</h2>
                    </div>
                    <a href="{{ route('panel.libros.create') }}" class="btn btn-outline">Subir un libro</a>
                </div>

                @if ($publishedBooks->isNotEmpty())
                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($publishedBooks as $book)
                            <article class="card">
                                <div class="card-body flex gap-4">
                                    <x-book-cover :book="$book" class="w-24 shrink-0" />
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-lg font-semibold text-slate-900"><a href="{{ route('libros.show', $book) }}" class="hover:text-brand-700">{{ $book->title }}</a></h3>
                                        <x-stars :value="$book->rating" :count="$book->reviews_total" class="mt-2" />
                                        <p class="mt-2 text-sm text-slate-600">Lecturas: {{ $book->reads_total }}</p>
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <a href="{{ route('leer.show', $book) }}" class="btn btn-primary btn-sm">Leer</a>
                                            <a href="{{ route('panel.libros.edit', $book) }}" class="btn btn-outline btn-sm">Editar</a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="card mt-6"><div class="card-body text-center text-slate-600">Aún no tienes libros publicados.</div></div>
                @endif
            </section>
        @endif
    </div>
@endsection
