@extends('layouts.app')

@section('title', $book->title)

@section('content')
    @php($user = auth()->user())
    @php($saved = $user && $user->hasSaved($book))

    <div class="container-page py-10">
        @if (! $book->isPublished())
            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                <div class="flex items-start gap-3">
                    <x-icon name="information-circle" class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" />
                    <p><strong>Borrador:</strong> este libro aún no es visible para el público general.</p>
                </div>
            </div>
        @endif

        <section class="grid gap-10 lg:grid-cols-[auto_1fr]">
            <div class="mx-auto w-full max-w-xs lg:mx-0">
                <x-book-cover :book="$book" class="mx-auto w-72 max-w-full" />
            </div>

            <div>
                <div class="flex flex-wrap items-center gap-3">
                    @if ($book->genre)
                        <a href="{{ route('catalogo', ['genero' => $book->genre->slug]) }}" class="badge badge-blue">{{ $book->genre->name }}</a>
                    @endif
                    <span class="badge badge-slate">PDF</span>
                    @if ($book->is_featured)
                        <span class="badge badge-brand">Destacado</span>
                    @endif
                </div>

                <h1 class="mt-4 text-4xl font-semibold text-slate-900 sm:text-5xl">{{ $book->title }}</h1>
                <p class="mt-4 text-base text-slate-700 sm:text-lg">por <a href="{{ route('autores.show', $book->user_id) }}" class="link">{{ $book->author?->name }}</a></p>

                <div class="mt-5 flex flex-wrap items-center gap-4">
                    <x-stars :value="$book->rating" :count="$book->reviews_count" size="lg" class="text-sm" />
                    <span class="text-sm text-slate-500">{{ $book->language }}</span>
                    @if ($book->pages)
                        <span class="text-sm text-slate-500">{{ $book->pages }} páginas</span>
                    @endif
                    @if ($book->hasPdf())
                        <span class="text-sm text-slate-500">{{ $book->pdf_size_label }}</span>
                    @endif
                </div>

                <div class="mt-8 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <a href="{{ route('leer.show', $book) }}" class="btn btn-primary">Leer en línea</a>
                        <a href="{{ route('leer.descargar', $book) }}" class="btn btn-outline">Descargar PDF</a>

                        @auth
                            @if ($book->user_id === $user->id)
                                <a href="{{ route('panel.libros.edit', $book) }}" class="btn btn-outline">Editar en el panel</a>
                            @elseif ($user->isAdmin())
                                <a href="{{ route('panel.libros.edit', $book) }}" class="btn btn-outline">Gestionar en el panel</a>
                            @else
                                <form action="{{ $saved ? route('biblioteca.quitar', $book) : route('biblioteca.guardar', $book) }}" method="POST">
                                    @csrf
                                    @if ($saved) @method('DELETE') @endif
                                    <button type="submit" class="btn btn-outline">{{ $saved ? 'Quitar de mi biblioteca' : 'Guardar en mi biblioteca' }}</button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline">Inicia sesión para guardar</a>
                        @endauth
                    </div>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Lecturas</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $book->reads_total }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Descargas</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $book->downloads_total }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Guardados</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $book->saves_total }}</p>
                    </div>
                </div>

                <div class="mt-10 grid items-start gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                    <article class="card">
                        <div class="card-body">
                            <h2 class="text-xl font-semibold text-slate-900">Sinopsis</h2>
                            <p class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $book->synopsis }}</p>
                        </div>
                    </article>

                    <article class="card">
                        <div class="card-body">
                            <h2 class="text-xl font-semibold text-slate-900">Detalles</h2>
                            <dl class="mt-4 space-y-3 text-sm">
                                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3"><dt class="font-medium text-slate-500">Autor</dt><dd class="text-right text-slate-900">{{ $book->author?->name }}</dd></div>
                                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3"><dt class="font-medium text-slate-500">Género</dt><dd class="text-right text-slate-900">{{ $book->genre?->name ?? 'Sin género' }}</dd></div>
                                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3"><dt class="font-medium text-slate-500">Idioma</dt><dd class="text-right text-slate-900">{{ $book->language }}</dd></div>
                                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3"><dt class="font-medium text-slate-500">Páginas</dt><dd class="text-right text-slate-900">{{ $book->pages ?: 'No especificadas' }}</dd></div>
                                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3"><dt class="font-medium text-slate-500">Tamaño del PDF</dt><dd class="text-right text-slate-900">{{ $book->hasPdf() ? $book->pdf_size_label : 'No disponible' }}</dd></div>
                                <div class="flex items-start justify-between gap-4"><dt class="font-medium text-slate-500">Publicado el</dt><dd class="text-right text-slate-900">{{ $book->published_at?->translatedFormat('d \\d\\e F \\d\\e Y') ?? 'Aún no publicado' }}</dd></div>
                            </dl>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="mt-14 grid items-start gap-8 xl:grid-cols-[1.1fr_0.9fr]">
            <div class="card">
                <div class="card-body">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wider text-slate-500">Reseñas</p>
                            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Opiniones de lectores</h2>
                            <p class="mt-2 text-sm text-slate-600">Valoraciones publicadas por usuarios registrados.</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-5 py-4 text-center">
                            <p class="text-4xl font-semibold text-slate-900">{{ number_format($book->rating, 1) }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $book->reviews_count }} {{ \Illuminate\Support\Str::plural('reseña', $book->reviews_count) }}</p>
                        </div>
                    </div>

                    <div class="mt-8 space-y-3">
                        @foreach ($reviewDistribution as $stars => $row)
                            <div class="flex items-center gap-3 text-sm">
                                <span class="w-6 font-medium text-slate-700">{{ $stars }}</span>
                                <x-icon name="star" solid class="h-4 w-4 text-amber-400" />
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-200">
                                    <div class="h-full rounded-full bg-amber-400" style="width: {{ $row['percent'] }}%"></div>
                                </div>
                                <span class="w-10 text-right text-slate-500">{{ $row['count'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-10 space-y-6">
                        @forelse ($book->reviews as $review)
                            <article class="rounded-lg border border-slate-200 p-5">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <p class="font-medium text-slate-900">{{ $review->user?->name ?? 'Usuario' }}</p>
                                            <x-stars :value="$review->rating" />
                                        </div>
                                        <p class="mt-2 text-sm text-slate-500">{{ $review->created_at->diffForHumans() }}</p>
                                    </div>
                                    @auth
                                        @if ($user->isAdmin() || $review->user_id === $user->id)
                                            <form action="{{ route('resenas.destroy', $review) }}" method="POST" onsubmit="return confirm('¿Deseas eliminar esta reseña?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger-outline btn-sm">Eliminar</button>
                                            </form>
                                        @endif
                                    @endauth
                                </div>
                                @if ($review->comment)
                                    <p class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $review->comment }}</p>
                                @endif
                            </article>
                        @empty
                            <div class="rounded-lg border border-dashed border-slate-300 px-6 py-10 text-center text-slate-500">
                                <x-icon name="information-circle" class="mx-auto h-10 w-10 text-slate-400" />
                                <p class="mt-3">Aún no hay reseñas para este libro.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <p class="text-xs uppercase tracking-wider text-slate-500">Participación</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900">Tu reseña</h2>

                    @auth
                        @if ($canReview)
                            <form action="{{ route('resenas.store', $book) }}" method="POST" class="mt-6 space-y-5" x-data="{ rating: '{{ old('rating', $userReview?->rating ?? 5) }}' }">
                                @csrf

                                <fieldset>
                                    <legend class="label">Calificación<span class="text-red-500"> *</span></legend>
                                    <div class="flex flex-wrap gap-3">
                                        @for ($i = 5; $i >= 1; $i--)
                                            <label class="flex cursor-pointer items-center gap-2 rounded-md border px-4 py-3 transition"
                                                   :class="Number(rating) === {{ $i }} ? 'border-amber-300 bg-amber-50 text-amber-800' : 'border-slate-200 bg-white text-slate-700'">
                                                <input type="radio" name="rating" value="{{ $i }}" x-model="rating" class="sr-only">
                                                <span class="font-medium">{{ $i }}</span>
                                                <x-icon name="star" solid class="h-4 w-4 text-amber-400" />
                                            </label>
                                        @endfor
                                    </div>
                                    @error('rating')
                                        <p class="error-text">{{ $message }}</p>
                                    @enderror
                                </fieldset>

                                <x-field name="comment" label="Comentario" type="textarea" :value="old('comment', $userReview?->comment)" rows="5" help="Comparte una opinión breve y útil para otros lectores." />

                                <button type="submit" class="btn btn-primary">{{ $userReview ? 'Actualizar reseña' : 'Guardar reseña' }}</button>
                            </form>
                        @else
                            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-600">
                                El autor del libro no puede publicar reseñas sobre su propia obra.
                            </div>
                        @endif
                    @else
                        <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-600">
                            Inicia sesión para guardar el libro en tu biblioteca y publicar una reseña.
                        </div>
                    @endauth
                </div>
            </div>
        </section>

        @if ($moreByAuthor->isNotEmpty())
            <section class="mt-14">
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500">Más publicaciones</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900">Más de este autor</h2>
                </div>
                <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 sm:gap-6">
                    @foreach ($moreByAuthor as $relatedBook)
                        <x-book-card :book="$relatedBook" />
                    @endforeach
                </div>
            </section>
        @endif

        @if ($similarBooks->isNotEmpty())
            <section class="mt-14">
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500">Recomendaciones</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900">También te puede interesar</h2>
                </div>
                <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 sm:gap-6">
                    @foreach ($similarBooks as $relatedBook)
                        <x-book-card :book="$relatedBook" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
