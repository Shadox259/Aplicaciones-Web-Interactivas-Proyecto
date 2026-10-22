@extends('layouts.app')

@section('title', $author->name)

@section('content')
    <div class="container-page py-10">
        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 bg-brand-900 px-6 py-8 text-white sm:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-5">
                        <div class="grid h-18 w-18 shrink-0 place-items-center rounded-lg bg-white/10 text-xl font-semibold text-white ring-1 ring-white/15">{{ $author->initials() }}</div>
                        <div>
                            <span class="badge bg-white/10 text-brand-200 ring-1 ring-white/15">{{ $author->roleLabel() }}</span>
                            <h1 class="mt-4 text-3xl font-semibold text-white">{{ $author->name }}</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-brand-100">{{ $author->bio ?: 'Este autor aún no ha publicado una biografía pública.' }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                            <p class="text-xs uppercase tracking-wider text-brand-200">Libros</p>
                            <p class="mt-2 text-2xl font-semibold text-white">{{ $publishedBooksCount }}</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                            <p class="text-xs uppercase tracking-wider text-brand-200">Promedio</p>
                            <p class="mt-2 text-2xl font-semibold text-white">{{ $averageRating ? number_format($averageRating, 1) : '—' }}</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                            <p class="text-xs uppercase tracking-wider text-brand-200">Lecturas</p>
                            <p class="mt-2 text-2xl font-semibold text-white">{{ $readsTotal }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-10">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500">Publicaciones</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900">Libros de este autor</h2>
                </div>
                <p class="text-sm text-slate-500">{{ $books->total() }} {{ \Illuminate\Support\Str::plural('libro', $books->total()) }}</p>
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
                    <div class="card-body py-14 text-center text-slate-600">
                        <x-icon name="book-open" class="mx-auto h-12 w-12 text-slate-400" />
                        <p class="mt-4">Todavía no hay libros publicados en este perfil.</p>
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
