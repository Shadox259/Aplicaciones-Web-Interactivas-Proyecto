@extends('layouts.panel')

@php
    $statusValue = old('status', $book->status ?: \App\Models\Book::STATUS_DRAFT);
    $authorValue = old('author_id', $book->user_id ?: auth()->id());
    $authorsMap = $authors->pluck('name', 'id');
@endphp

@section('title', $isEdit ? 'Editar libro' : 'Subir libro')

@section('subtitle')
    {{ $isEdit ? 'Actualiza la información del libro, reemplaza archivos y controla su visibilidad pública.' : 'Completa los datos del libro y carga el PDF que estará disponible para lectura y descarga.' }}
@endsection

@section('actions')
    <a href="{{ route('panel.libros.index') }}" class="btn btn-outline">
        <x-icon name="arrow-left" class="h-5 w-5" />
        Volver al listado
    </a>
@endsection

@section('content')
    <form method="POST"
          action="{{ $isEdit ? route('panel.libros.update', $book) : route('panel.libros.store') }}"
          enctype="multipart/form-data"
          x-data="{
              form: {
                  title: @js(old('title', $book->title)),
                  authorId: @js((string) $authorValue),
              },
              authors: @js($authorsMap),
              coverPreview: null,
              updateCover(event) {
                  const file = event.target.files[0];
                  if (!file) {
                      this.coverPreview = null;
                      return;
                  }

                  const reader = new FileReader();
                  reader.onload = (e) => this.coverPreview = e.target.result;
                  reader.readAsDataURL(file);
              },
              authorName() {
                  return this.authors[this.form.authorId] ?? @js($book->author?->name ?? auth()->user()->name);
              },
          }"
          class="grid gap-8 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.9fr)]">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="space-y-6">
            <section class="card">
                <div class="card-body space-y-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Información</h2>
                        <p class="mt-1 text-sm text-slate-500">Datos principales que se mostrarán en la ficha pública del libro.</p>
                    </div>

                    <x-field name="title" label="Título" :value="$book->title" required x-model="form.title" />
                    <x-field name="synopsis" label="Sinopsis" type="textarea" :value="$book->synopsis" rows="7" required />

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-field name="genre_id" label="Género" type="select">
                            <option value="">Sin género</option>
                            @foreach ($genres as $genre)
                                <option value="{{ $genre->id }}" @selected((string) old('genre_id', $book->genre_id) === (string) $genre->id)>{{ $genre->name }}</option>
                            @endforeach
                        </x-field>

                        <x-field name="language" label="Idioma" :value="$book->language ?: 'Español'" required />
                    </div>

                    <x-field name="pages" label="Páginas" type="number" :value="$book->pages" min="1" max="20000" />
                </div>
            </section>

            <section class="card">
                <div class="card-body space-y-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Archivos</h2>
                        <p class="mt-1 text-sm text-slate-500">El PDF se guarda de forma privada y se publica para lectura y descarga cuando el libro está visible.</p>
                    </div>

                    <x-field name="pdf" label="Archivo PDF" type="file" accept="application/pdf"
                             help="PDF, máx. 30 MB. Los borradores solo pueden consultarlos el autor y el administrador." />

                    @if ($isEdit && $book->hasPdf())
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                            <p class="font-semibold text-slate-900">Archivo actual</p>
                            <p class="mt-1">{{ $book->pdf_original_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">Tamaño: {{ $book->pdf_size_label }}</p>
                            <a href="{{ route('leer.pdf', $book) }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-2 link">
                                <x-icon name="document-text" class="h-4 w-4" />
                                Ver PDF
                            </a>
                            <p class="mt-2 text-xs text-slate-500">Si subes un archivo nuevo, el PDF actual se reemplazará.</p>
                        </div>
                    @endif

                    <x-field name="cover" label="Portada" type="file" accept="image/png,image/jpeg,image/webp"
                             help="JPG, PNG o WEBP. La nueva imagen sustituirá la portada actual." x-on:change="updateCover($event)" />

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg border border-slate-200 p-4">
                            <p class="text-sm font-semibold text-slate-700">Vista previa de portada</p>
                            <div class="mt-3 flex justify-center">
                                <template x-if="coverPreview">
                                    <img :src="coverPreview" alt="Vista previa de portada" class="aspect-[2/3] w-40 rounded-md object-cover shadow-sm">
                                </template>
                                <div x-show="!coverPreview" class="w-40">
                                    <x-book-cover :book="$book" class="w-40" />
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-200 p-4 text-sm text-slate-600">
                            <p class="font-semibold text-slate-900">Indicaciones</p>
                            <ul class="mt-3 space-y-2">
                                <li class="flex gap-2"><x-icon name="check-circle" class="mt-0.5 h-4 w-4 text-emerald-500" /> Usa un PDF final y sin contraseña.</li>
                                <li class="flex gap-2"><x-icon name="check-circle" class="mt-0.5 h-4 w-4 text-emerald-500" /> Verifica que la portada conserve buena legibilidad en tamaños pequeños.</li>
                                <li class="flex gap-2"><x-icon name="information-circle" class="mt-0.5 h-4 w-4 text-brand-600" /> Los libros publicados pueden leerse y descargarse públicamente.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-body space-y-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Publicación</h2>
                        <p class="mt-1 text-sm text-slate-500">Para publicar el libro es obligatorio haber cargado un PDF.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-field name="status" label="Estado" type="select" required>
                            <option value="draft" @selected($statusValue === 'draft')>Borrador</option>
                            <option value="published" @selected($statusValue === 'published')>Publicado</option>
                        </x-field>

                        @if (! $isEdit && $isAdmin)
                            <x-field name="author_id" label="Autor" type="select" required x-model="form.authorId">
                                @foreach ($authors as $author)
                                    <option value="{{ $author->id }}" @selected((string) $authorValue === (string) $author->id)>
                                        {{ $author->name }} · {{ $author->roleLabel() }}
                                    </option>
                                @endforeach
                            </x-field>
                        @endif
                    </div>

                    <div class="rounded-lg border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">
                        <div class="flex items-start gap-3">
                            <x-icon name="information-circle" class="mt-0.5 h-5 w-5" />
                            <p>
                                Los borradores solo están disponibles para su autor y para el administrador. Al publicar el libro, cualquier visitante podrá leerlo en línea o descargarlo.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="btn btn-primary">
                            <x-icon name="check" class="h-5 w-5" />
                            Guardar
                        </button>
                        <a href="{{ route('panel.libros.index') }}" class="btn btn-outline">Cancelar</a>
                    </div>
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card sticky top-24">
                <div class="card-body">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Vista previa</h2>
                            <p class="mt-1 text-sm text-slate-500">Referencia de cómo se mostrará la tarjeta del libro.</p>
                        </div>
                        <span class="badge {{ $statusValue === 'published' ? 'badge-green' : 'badge-slate' }}">
                            {{ $statusValue === 'published' ? 'Publicado' : 'Borrador' }}
                        </span>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 bg-slate-50 px-6 pb-4 pt-6">
                            <template x-if="coverPreview">
                                <img :src="coverPreview" alt="Portada del libro" class="mx-auto aspect-[2/3] w-40 rounded-md object-cover shadow-sm">
                            </template>
                            <div x-show="!coverPreview" class="mx-auto w-40">
                                <x-book-cover :book="$book" class="w-40" />
                            </div>
                        </div>
                        <div class="space-y-3 p-4">
                            <p class="text-[11px] uppercase tracking-wider text-slate-500">{{ $book->genre?->name ?? 'Sin género' }}</p>
                            <h3 class="text-xl font-semibold text-slate-900" x-text="form.title || 'Título del libro'"></h3>
                            <p class="text-sm text-slate-600">
                                por <span class="font-medium text-brand-700" x-text="authorName()"></span>
                            </p>
                            <x-stars :value="$book->rating" :count="$book->reviews_total" />
                        </div>
                    </div>
                </div>
            </section>
        </aside>
    </form>
@endsection
