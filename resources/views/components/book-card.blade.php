{{--
    Tarjeta de libro para listados (inicio, catalogo, autor, relacionados).
    Requiere cargar antes: author, genre  y  ->withRating()  (evita consultas N+1).
    Plataforma gratuita: todos leen el PDF; quien tiene sesion puede guardarlo en "Mi biblioteca".
    Uso: <x-book-card :book="$book" />
--}}
@props(['book'])
@php
    $user = auth()->user();
    $saved = $user && $user->hasSaved($book);
    $isMine = $user && $user->id === $book->user_id;
@endphp
<article class="group flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white transition hover:border-slate-300 hover:shadow-md">
    <a href="{{ route('libros.show', $book) }}" class="relative block border-b border-slate-100 bg-slate-50 px-6 pb-4 pt-6" aria-label="Ver {{ $book->title }}">
        <x-book-cover :book="$book" class="mx-auto w-3/5 min-w-28" />
        @if ($book->is_featured)
            <span class="badge badge-brand absolute right-3 top-3"><x-icon name="star" solid class="h-3 w-3" /> Destacado</span>
        @endif
    </a>

    <div class="flex flex-1 flex-col gap-1.5 p-4">
        @if ($book->genre)
            <a href="{{ route('catalogo', ['genero' => $book->genre->slug]) }}" class="w-fit text-[11px] font-medium uppercase tracking-wider text-slate-500 hover:text-brand-700">{{ $book->genre->name }}</a>
        @endif

        <h3 class="line-clamp-2 min-h-[2.5rem] text-[15px] font-semibold leading-snug text-slate-900">
            <a href="{{ route('libros.show', $book) }}" class="hover:text-brand-700">{{ $book->title }}</a>
        </h3>

        <p class="text-sm text-slate-600">
            <a href="{{ route('autores.show', $book->user_id) }}" class="hover:text-brand-700 hover:underline">{{ $book->author?->name ?? 'Autor desconocido' }}</a>
        </p>

        <x-stars :value="$book->rating" :count="$book->reviews_total" />

        <div class="mt-auto flex items-center gap-2 pt-3">
            <a href="{{ route('leer.show', $book) }}" class="btn btn-primary btn-sm flex-1"><x-icon name="book-open" class="h-4 w-4" /> Leer</a>

            @if ($isMine)
                <a href="{{ route('panel.libros.edit', $book) }}" class="btn btn-outline btn-sm" title="Editar libro" aria-label="Editar {{ $book->title }}"><x-icon name="pencil-square" class="h-4 w-4" /></a>
            @elseif ($user)
                <form method="POST" action="{{ $saved ? route('biblioteca.quitar', $book) : route('biblioteca.guardar', $book) }}">
                    @csrf
                    @if ($saved) @method('DELETE') @endif
                    <button type="submit" class="btn btn-outline btn-sm {{ $saved ? 'border-brand-300 text-brand-700' : '' }}"
                            title="{{ $saved ? 'Quitar de mi biblioteca' : 'Guardar en mi biblioteca' }}"
                            aria-label="{{ $saved ? 'Quitar de mi biblioteca' : 'Guardar en mi biblioteca' }}: {{ $book->title }}">
                        <x-icon name="bookmark" :solid="$saved" class="h-4 w-4" />
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline btn-sm" title="Inicia sesión para guardar" aria-label="Inicia sesión para guardar {{ $book->title }}"><x-icon name="bookmark" class="h-4 w-4" /></a>
            @endif
        </div>
    </div>
</article>
