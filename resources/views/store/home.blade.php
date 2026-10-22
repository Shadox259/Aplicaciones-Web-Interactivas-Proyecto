@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
    @php($user = auth()->user())
    @php($decorativePositions = ['lg:col-start-1', 'lg:col-start-2', 'lg:col-start-3'])

    <section class="border-b border-slate-200 bg-brand-900 text-white">
        <div class="container-page grid gap-10 py-14 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
            <div>
                <span class="badge bg-white/10 text-brand-200 ring-1 ring-white/15">Biblioteca digital</span>
                <h1 class="mt-5 text-4xl font-semibold tracking-tight text-white sm:text-5xl">Bienvenido a Library of Wonderland</h1>
                <p class="mt-4 max-w-2xl text-base leading-8 text-brand-100">
                    Explora un catálogo de libros en PDF, léelos en línea, descárgalos y guarda tus favoritos en una biblioteca personal.
                </p>

                <form action="{{ route('catalogo') }}" method="GET" class="mt-8 max-w-2xl">
                    <label for="home-q" class="sr-only">Buscar en el catálogo</label>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <div class="relative flex-1">
                            <x-icon name="magnifying-glass" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                            <input id="home-q" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por título, sinopsis o autor"
                                   class="w-full rounded-md border border-transparent bg-white py-3.5 pl-12 pr-4 text-base text-slate-900 placeholder:text-slate-400 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-200">
                        </div>
                        <button type="submit" class="btn btn-light btn-lg">Buscar libros</button>
                    </div>
                </form>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('catalogo') }}" class="btn btn-light btn-lg">Explorar catálogo</a>
                    @if (! $user)
                        <a href="{{ route('registro', ['tipo' => 'autor']) }}" class="btn btn-outline btn-lg border-white/20 bg-transparent text-white hover:bg-white/10 hover:text-white">Publicar un libro</a>
                    @elseif ($user->canPublish())
                        <a href="{{ route('panel.libros.create') }}" class="btn btn-outline btn-lg border-white/20 bg-transparent text-white hover:bg-white/10 hover:text-white">Publicar un libro</a>
                    @endif
                </div>

                <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                        <p class="text-xs uppercase tracking-wider text-brand-200">Libros publicados</p>
                        <p class="mt-2 text-2xl font-semibold text-white">{{ $stats['books'] }}</p>
                    </div>
                    <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                        <p class="text-xs uppercase tracking-wider text-brand-200">Autores activos</p>
                        <p class="mt-2 text-2xl font-semibold text-white">{{ $stats['authors'] }}</p>
                    </div>
                    <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                        <p class="text-xs uppercase tracking-wider text-brand-200">Géneros</p>
                        <p class="mt-2 text-2xl font-semibold text-white">{{ $stats['genres'] }}</p>
                    </div>
                    <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                        <p class="text-xs uppercase tracking-wider text-brand-200">Reseñas</p>
                        <p class="mt-2 text-2xl font-semibold text-white">{{ $stats['reviews'] }}</p>
                    </div>
                </div>
            </div>

            <div class="hidden lg:block">
                <div class="grid grid-cols-3 gap-4">
                    @foreach ($decorativeBooks as $index => $decorativeBook)
                        <div class="{{ $decorativePositions[$index] ?? '' }}">
                            <x-book-cover :book="$decorativeBook" class="w-full" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <div class="container-page py-10">
        @if ($genres->isNotEmpty())
            <section>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500">Explorar</p>
                        <h2 class="mt-1 text-2xl font-semibold text-slate-900">Géneros</h2>
                    </div>
                    <a href="{{ route('catalogo') }}" class="link hidden sm:inline-flex">Ver el catálogo completo</a>
                </div>
                <div class="mt-5 flex flex-wrap gap-3">
                    @foreach ($genres as $genre)
                        <a href="{{ route('catalogo', ['genero' => $genre->slug]) }}" class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 shadow-sm transition hover:border-brand-300 hover:text-brand-700">
                            <span>{{ $genre->name }}</span>
                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-500">{{ $genre->books_count }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="mt-12">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500">Selección editorial</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900">Libros destacados</h2>
                </div>
                <a href="{{ route('catalogo') }}" class="btn btn-outline hidden sm:inline-flex">Ver más</a>
            </div>
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 sm:gap-6">
                @foreach ($featuredBooks as $book)
                    <x-book-card :book="$book" />
                @endforeach
            </div>
        </section>

        <section class="mt-12">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500">Actividad</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900">Más leídos</h2>
                </div>
                <a href="{{ route('catalogo', ['orden' => 'populares']) }}" class="btn btn-outline hidden sm:inline-flex">Ver ranking</a>
            </div>
            <div class="mt-6 grid gap-4 lg:grid-cols-2 xl:grid-cols-5">
                @foreach ($mostReadBooks as $book)
                    <article class="card">
                        <div class="card-body">
                            <x-book-cover :book="$book" class="mx-auto w-28" />
                            <h3 class="mt-4 text-base font-semibold text-slate-900">
                                <a href="{{ route('libros.show', $book) }}" class="hover:text-brand-700">{{ $book->title }}</a>
                            </h3>
                            <p class="mt-1 text-sm text-slate-600">{{ $book->author?->name }}</p>
                            <dl class="mt-4 space-y-2 text-sm text-slate-600">
                                <div class="flex items-center justify-between"><dt>Lecturas</dt><dd class="font-medium text-slate-900">{{ $book->reads_total }}</dd></div>
                                <div class="flex items-center justify-between"><dt>Descargas</dt><dd class="font-medium text-slate-900">{{ $book->downloads_total }}</dd></div>
                            </dl>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="mt-12">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500">Novedades</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900">Publicaciones recientes</h2>
                </div>
                <a href="{{ route('catalogo', ['orden' => 'recientes']) }}" class="btn btn-outline hidden sm:inline-flex">Ver novedades</a>
            </div>
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 sm:gap-6">
                @foreach ($newBooks as $book)
                    <x-book-card :book="$book" />
                @endforeach
            </div>
        </section>

        <section class="mt-12">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500">Comunidad</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900">Mejor valorados</h2>
                </div>
                <a href="{{ route('catalogo', ['orden' => 'valorados']) }}" class="btn btn-outline hidden sm:inline-flex">Ver reseñas destacadas</a>
            </div>
            <div class="mt-6 grid gap-6 lg:grid-cols-2 xl:grid-cols-4">
                @forelse ($topRatedBooks as $book)
                    <article class="card">
                        <div class="card-body flex gap-4">
                            <x-book-cover :book="$book" class="w-24 shrink-0" />
                            <div class="min-w-0">
                                <p class="text-xs uppercase tracking-wider text-slate-500">Posición {{ $loop->iteration }}</p>
                                <h3 class="mt-2 text-lg font-semibold text-slate-900">
                                    <a href="{{ route('libros.show', $book) }}" class="hover:text-brand-700">{{ $book->title }}</a>
                                </h3>
                                <p class="mt-1 text-sm text-slate-600">{{ $book->author?->name }}</p>
                                <x-stars :value="$book->rating" :count="$book->reviews_total" class="mt-3" />
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="card lg:col-span-2 xl:col-span-4">
                        <div class="card-body text-center text-slate-600">
                            <x-icon name="information-circle" class="mx-auto h-10 w-10 text-slate-400" />
                            <p class="mt-3">Aún no hay reseñas suficientes para destacar libros en esta sección.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="mt-12 grid gap-6 lg:grid-cols-3">
            <article class="card">
                <div class="card-body">
                    <span class="stat-icon"><x-icon name="book-open" class="h-5 w-5" /></span>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">Lectura en línea</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">Abre los libros publicados en un lector PDF integrado y navega por su contenido desde el navegador.</p>
                </div>
            </article>
            <article class="card">
                <div class="card-body">
                    <span class="stat-icon"><x-icon name="bookmark" class="h-5 w-5" /></span>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">Biblioteca personal</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">Guarda títulos para consultarlos rápidamente y mantener un seguimiento ordenado de tus lecturas.</p>
                </div>
            </article>
            <article class="card">
                <div class="card-body">
                    <span class="stat-icon"><x-icon name="cloud-arrow-up" class="h-5 w-5" /></span>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">Publicación de obras</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-600">Autores y administradores disponen de un panel para organizar libros, portadas y archivos PDF.</p>
                </div>
            </article>
        </section>

        <section class="mt-12 rounded-lg bg-brand-900 px-6 py-10 text-white">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs uppercase tracking-wider text-brand-200">Para autores</p>
                    <h2 class="mt-2 text-3xl font-semibold text-white">Comparte tu catálogo con la comunidad</h2>
                    <p class="mt-3 text-sm leading-7 text-brand-100 sm:text-base">Sube tus libros en PDF, completa la información bibliográfica y mantén una presencia editorial clara y profesional.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    @if (! $user)
                        <a href="{{ route('registro', ['tipo' => 'autor']) }}" class="btn btn-light btn-lg">Crear cuenta de autor</a>
                    @elseif ($user->canPublish())
                        <a href="{{ route('panel.libros.create') }}" class="btn btn-light btn-lg">Subir un libro</a>
                    @endif
                    <a href="{{ route('catalogo') }}" class="btn btn-outline btn-lg border-white/20 bg-transparent text-white hover:bg-white/10 hover:text-white">Explorar catálogo</a>
                </div>
            </div>
        </section>
    </div>
@endsection
