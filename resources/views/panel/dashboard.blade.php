@extends('layouts.panel')

@section('title', 'Resumen')

@section('subtitle')
    Hola, {{ $user->name }}. Consulta la actividad reciente de tu panel de {{ \Illuminate\Support\Str::lower($user->roleLabel()) }}.
@endsection

@section('actions')
    <a href="{{ route('panel.libros.create') }}" class="btn btn-primary">
        <x-icon name="cloud-arrow-up" class="h-5 w-5" />
        Subir libro
    </a>
@endsection

@section('content')
    <div class="space-y-6">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @if ($mode === 'admin')
                <article class="stat-card">
                    <div class="stat-icon"><x-icon name="users" class="h-6 w-6" /></div>
                    <div>
                        <p class="text-sm text-slate-500">Usuarios</p>
                        <p class="text-2xl font-semibold text-slate-900">{{ number_format($stats['users_total']) }}</p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $stats['users_admins'] }} admin · {{ $stats['users_authors'] }} autores · {{ $stats['users_customers'] }} clientes
                        </p>
                    </div>
                </article>
            @endif

            <article class="stat-card">
                <div class="stat-icon"><x-icon name="rectangle-stack" class="h-6 w-6" /></div>
                <div>
                    <p class="text-sm text-slate-500">Libros</p>
                    <p class="text-2xl font-semibold text-slate-900">{{ number_format($stats['published_books']) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ number_format($stats['draft_books']) }} en borrador</p>
                </div>
            </article>

            <article class="stat-card">
                <div class="stat-icon"><x-icon name="book-open" class="h-6 w-6" /></div>
                <div>
                    <p class="text-sm text-slate-500">Lecturas</p>
                    <p class="text-2xl font-semibold text-slate-900">{{ number_format($stats['reads_total']) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ number_format($stats['reads_last_7']) }} en los últimos 7 días</p>
                </div>
            </article>

            <article class="stat-card">
                <div class="stat-icon"><x-icon name="arrow-down-tray" class="h-6 w-6" /></div>
                <div>
                    <p class="text-sm text-slate-500">Descargas</p>
                    <p class="text-2xl font-semibold text-slate-900">{{ number_format($stats['downloads_total']) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ number_format($stats['saves_total']) }} guardados</p>
                </div>
            </article>

            <article class="stat-card">
                <div class="stat-icon"><x-icon name="star" class="h-6 w-6" /></div>
                <div>
                    <p class="text-sm text-slate-500">Reseñas</p>
                    <p class="text-2xl font-semibold text-slate-900">{{ number_format($stats['reviews_total']) }}</p>
                    <p class="mt-1 text-xs text-slate-500">Promedio {{ number_format($stats['reviews_average'], 1) }}/5</p>
                </div>
            </article>
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
            <section class="card flex flex-col">
                <div class="card-body flex flex-1 flex-col">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Lecturas de los últimos 7 días</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                @if ($mode === 'admin')
                                    Lecturas en línea registradas en toda la plataforma.
                                @else
                                    Lecturas en línea registradas en tus libros publicados.
                                @endif
                            </p>
                        </div>
                        <span class="badge badge-slate">7 días</span>
                    </div>

                    <div class="mt-6 grid min-h-64 flex-1 grid-cols-7 gap-3 rounded-lg bg-slate-50 p-4">
                        @foreach ($readsChart as $day)
                            <div class="flex min-w-0 flex-col justify-end">
                                <div class="group relative flex flex-1 items-end justify-center">
                                    <div class="w-full rounded-t-md bg-brand-700"
                                         style="height: {{ $day['height'] }}%"
                                         title="{{ $day['label'] }} · {{ $day['reads'] }} lecturas"></div>
                                    <div class="pointer-events-none absolute inset-x-0 bottom-full mb-2 hidden rounded-md bg-slate-900 px-3 py-2 text-center text-xs text-white group-hover:block">
                                        <p class="font-semibold">{{ $day['label'] }}</p>
                                        <p>{{ $day['reads'] }} lecturas</p>
                                    </div>
                                </div>
                                <div class="mt-3 text-center">
                                    <p class="text-[11px] uppercase tracking-wider text-slate-500">{{ $day['short_label'] }}</p>
                                    <p class="mt-1 text-xs font-medium text-slate-700">{{ $day['reads'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-body">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Libros más leídos</h2>
                        <p class="mt-1 text-sm text-slate-500">Top 5 por lecturas acumuladas.</p>
                    </div>

                    @if ($topBooks->isEmpty())
                        <div class="mt-6 rounded-lg border border-dashed border-slate-300 px-6 py-10 text-center">
                            <x-icon name="book-open" class="mx-auto h-10 w-10 text-slate-300" />
                            <p class="mt-3 text-sm font-medium text-slate-700">Aún no hay actividad suficiente para generar el ranking.</p>
                        </div>
                    @else
                        <ol class="mt-5 divide-y divide-slate-100">
                            @foreach ($topBooks as $book)
                                <li class="flex items-center gap-3 py-3">
                                    <span class="w-5 shrink-0 text-center text-sm font-semibold text-slate-400">{{ $loop->iteration }}</span>
                                    <a href="{{ route('libros.show', $book) }}" class="block w-10 shrink-0">
                                        <x-book-cover :book="$book" class="w-10" />
                                    </a>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('libros.show', $book) }}" class="line-clamp-2 text-sm font-semibold leading-snug text-slate-900 hover:text-brand-700">
                                            {{ $book->title }}
                                        </a>
                                        <p class="text-xs text-slate-500">{{ $book->author?->name }}</p>
                                    </div>
                                    <dl class="flex shrink-0 gap-3 text-right text-xs text-slate-500">
                                        <div class="flex flex-col-reverse"><dt>lecturas</dt><dd class="text-sm font-semibold text-slate-900">{{ number_format($book->reads_total) }}</dd></div>
                                        <div class="hidden flex-col-reverse sm:flex"><dt>descargas</dt><dd class="text-sm font-semibold text-slate-900">{{ number_format($book->downloads_total) }}</dd></div>
                                        <div class="hidden flex-col-reverse md:flex"><dt>guardados</dt><dd class="text-sm font-semibold text-slate-900">{{ number_format($book->saves_total) }}</dd></div>
                                    </dl>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </section>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <section class="space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Últimas reseñas</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        @if ($mode === 'admin')
                            Comentarios recientes publicados por los lectores.
                        @else
                            Comentarios recientes recibidos por tus libros.
                        @endif
                    </p>
                </div>

                @if ($latestReviews->isEmpty())
                    <div class="card">
                        <div class="card-body text-center">
                            <x-icon name="information-circle" class="mx-auto h-10 w-10 text-slate-300" />
                            <p class="mt-3 text-sm font-medium text-slate-700">Todavía no hay reseñas registradas.</p>
                        </div>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($latestReviews as $review)
                            <article class="card">
                                <div class="card-body">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <a href="{{ route('libros.show', $review->book) }}" class="font-semibold text-slate-900 hover:text-brand-700">
                                                {{ $review->book?->title }}
                                            </a>
                                            <p class="mt-1 text-sm text-slate-500">{{ $review->user?->name ?? 'Usuario' }}</p>
                                        </div>
                                        <x-stars :value="$review->rating" />
                                    </div>
                                    @if (filled($review->comment))
                                        <p class="mt-3 text-sm text-slate-700">{{ $review->comment }}</p>
                                    @endif
                                    <p class="mt-3 text-xs text-slate-500">{{ $review->created_at->diffForHumans() }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            @if ($mode === 'admin')
                <section class="space-y-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Usuarios recientes</h2>
                        <p class="mt-1 text-sm text-slate-500">Últimas cuentas creadas en la plataforma.</p>
                    </div>

                    @if ($recentUsers->isEmpty())
                        <div class="card">
                            <div class="card-body text-center">
                                <x-icon name="users" class="mx-auto h-10 w-10 text-slate-300" />
                                <p class="mt-3 text-sm font-medium text-slate-700">No hay usuarios recientes para mostrar.</p>
                            </div>
                        </div>
                    @else
                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Libros</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($recentUsers as $recentUser)
                                    <tr>
                                        <td>
                                            <p class="font-semibold text-slate-900">{{ $recentUser->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $recentUser->email }}</p>
                                        </td>
                                        <td><span class="badge badge-brand">{{ $recentUser->roleLabel() }}</span></td>
                                        <td>
                                            <span class="badge {{ $recentUser->is_active ? 'badge-green' : 'badge-red' }}">
                                                {{ $recentUser->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td>{{ $recentUser->books_count }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>
@endsection
