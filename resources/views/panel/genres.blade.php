@extends('layouts.panel')

@section('title', 'Géneros')

@section('subtitle')
    Crea, edita y organiza las categorías del catálogo. Si eliminas un género, los libros quedarán sin género asignado.
@endsection

@section('content')
    <div class="space-y-6">
        <section class="card">
            <div class="card-body">
                <div class="mb-5">
                    <h2 class="text-xl font-semibold">Crear género</h2>
                    <p class="mt-1 text-sm text-slate-500">Añade nuevas categorías para clasificar mejor los libros.</p>
                </div>

                <form method="POST" action="{{ route('panel.generos.store') }}" class="grid gap-4 md:grid-cols-2">
                    @csrf
                    <x-field name="name" label="Nombre" :value="old('name')" required />
                    <x-field name="description" label="Descripción" :value="old('description')" help="Breve descripción visible para el equipo." />

                    <div class="md:col-span-2">
                        <button type="submit" class="btn btn-primary">
                            <x-icon name="plus" class="h-5 w-5" />
                            Guardar género
                        </button>
                    </div>
                </form>
            </div>
        </section>

        @if ($genres->isEmpty())
            <section class="card">
                <div class="card-body py-14 text-center">
                    <x-icon name="tag" class="mx-auto h-12 w-12 text-slate-300" />
                    <h2 class="mt-4 text-xl font-semibold">Todavía no hay géneros</h2>
                    <p class="mt-2 text-sm text-slate-500">Crea el primer género para empezar a clasificar el catálogo.</p>
                </div>
            </section>
        @else
            <section class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Slug</th>
                        <th>Descripción</th>
                        <th>Libros</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($genres as $genre)
                        <tr x-data="{ editing: false }">
                            <td class="min-w-[180px]">
                                <span x-show="!editing" class="font-semibold text-brand-950">{{ $genre->name }}</span>
                                <input x-show="editing" x-cloak form="genre-edit-{{ $genre->id }}" type="text" name="name"
                                       value="{{ old('name', $genre->name) }}" class="input">
                            </td>
                            <td class="text-slate-500">{{ $genre->slug }}</td>
                            <td class="min-w-[220px]">
                                <span x-show="!editing" class="text-sm text-slate-600">{{ $genre->description ?: 'Sin descripción' }}</span>
                                <input x-show="editing" x-cloak form="genre-edit-{{ $genre->id }}" type="text" name="description"
                                       value="{{ old('description', $genre->description) }}" class="input">
                            </td>
                            <td>{{ $genre->books_count }}</td>
                            <td>
                                <div class="flex min-w-[250px] flex-wrap gap-2">
                                    <form id="genre-edit-{{ $genre->id }}" method="POST" action="{{ route('panel.generos.update', $genre) }}">
                                        @csrf
                                        @method('PUT')
                                    </form>

                                    <button type="button" class="btn btn-outline btn-sm" x-show="!editing" @click="editing = true">
                                        Editar
                                    </button>
                                    <button type="submit" form="genre-edit-{{ $genre->id }}" class="btn btn-primary btn-sm" x-show="editing" x-cloak>
                                        Guardar
                                    </button>
                                    <button type="button" class="btn btn-ghost btn-sm" x-show="editing" x-cloak @click="editing = false">
                                        Cancelar
                                    </button>

                                    <form method="POST" action="{{ route('panel.generos.destroy', $genre) }}"
                                          onsubmit="return confirm('{{ $genre->books_count > 0 ? 'Este género tiene libros asociados. Si continúas, quedarán sin género. ¿Deseas eliminarlo?' : '¿Eliminar este género?' }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger-outline btn-sm">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </section>
        @endif
    </div>
@endsection
