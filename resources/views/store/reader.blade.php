@extends('layouts.app')

@section('title', 'Lector · '.$book->title)

@section('content')
    @php($user = auth()->user())
    @php($saved = $user && $user->hasSaved($book))

    <div class="container-page py-10">
        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 bg-white px-5 py-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm text-slate-500">{{ $book->author?->name }}</p>
                        <h1 class="text-2xl font-semibold text-slate-900 sm:text-3xl">{{ $book->title }}</h1>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('catalogo') }}" class="btn btn-outline">
                            <x-icon name="arrow-left" class="h-4 w-4" />
                            Volver
                        </a>
                        @auth
                            @if ($book->user_id !== $user->id)
                                <form action="{{ $saved ? route('biblioteca.quitar', $book) : route('biblioteca.guardar', $book) }}" method="POST">
                                    @csrf
                                    @if ($saved) @method('DELETE') @endif
                                    <button type="submit" class="btn btn-outline">{{ $saved ? 'Quitar de mi biblioteca' : 'Guardar en mi biblioteca' }}</button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline">Inicia sesión para guardar</a>
                        @endauth
                        <a href="{{ route('leer.descargar', $book) }}" class="btn btn-outline">Descargar PDF</a>
                        <a href="{{ route('leer.pdf', $book) }}" target="_blank" rel="noopener" class="btn btn-primary">Abrir en pestaña nueva</a>
                    </div>
                </div>
            </div>

            <div class="bg-slate-50 p-5">
                @if ($book->hasPdf())
                    <iframe src="{{ route('leer.pdf', $book) }}#toolbar=1&navpanes=0&view=FitH" title="Lector del libro {{ $book->title }}" class="h-[82vh] w-full rounded-lg border bg-white shadow-sm">
                        Tu navegador no admite la vista integrada. Puedes abrir o descargar el PDF desde los botones superiores.
                    </iframe>
                @else
                    <div class="rounded-lg border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                        <x-icon name="information-circle" class="mx-auto h-14 w-14 text-slate-400" />
                        <h2 class="mt-5 text-2xl font-semibold text-slate-900">Este libro aún no tiene un PDF disponible</h2>
                        <p class="mt-3 text-sm text-slate-600">Cuando el archivo esté listo, podrás consultarlo aquí o descargarlo desde esta misma página.</p>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
