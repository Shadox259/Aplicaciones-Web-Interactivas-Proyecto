@extends('layouts.app')

@section('title', 'Crear cuenta')

@section('content')
    <div class="container-page py-10">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="grid lg:grid-cols-[0.95fr_1.05fr]">
                <section class="bg-brand-900 px-8 py-12 text-white sm:px-12">
                    <span class="badge bg-white/10 text-brand-200 ring-1 ring-white/15">Registro</span>
                    <h1 class="mt-6 text-3xl font-semibold text-white">Crea tu cuenta</h1>
                    <p class="mt-4 max-w-xl text-sm leading-7 text-brand-100 sm:text-base">
                        Regístrate para guardar libros, participar con reseñas y, si eres autor, administrar tus publicaciones.
                    </p>

                    <ul class="mt-8 space-y-4 text-sm text-brand-100">
                        <li class="flex gap-3"><x-icon name="book-open" class="mt-0.5 h-5 w-5 shrink-0 text-brand-200" /> Organiza tus lecturas en una biblioteca personal.</li>
                        <li class="flex gap-3"><x-icon name="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-brand-200" /> Comparte comentarios útiles para otros lectores.</li>
                        <li class="flex gap-3"><x-icon name="cloud-arrow-up" class="mt-0.5 h-5 w-5 shrink-0 text-brand-200" /> Gestiona libros y archivos PDF desde el panel de autor.</li>
                    </ul>
                </section>

                <section class="bg-surface px-6 py-10 sm:px-10 lg:px-12">
                    <div class="mx-auto max-w-xl">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-6">
                                    <h2 class="text-2xl font-semibold text-slate-900">Crear cuenta</h2>
                                    <p class="mt-2 text-sm text-slate-600">Completa tus datos y selecciona el tipo de cuenta.</p>
                                </div>

                                <form action="{{ route('registro.store') }}" method="POST" class="space-y-5">
                                    @csrf

                                    <x-field name="name" label="Nombre completo" required autocomplete="name" />
                                    <x-field name="email" label="Correo electrónico" type="email" required autocomplete="email" />
                                    <x-field name="password" label="Contraseña" type="password" required autocomplete="new-password" help="Mínimo 8 caracteres." />
                                    <x-field name="password_confirmation" label="Confirmar contraseña" type="password" required autocomplete="new-password" />

                                    <fieldset>
                                        <legend class="label">Tipo de cuenta<span class="text-red-500"> *</span></legend>
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <label class="block cursor-pointer">
                                                <input type="radio" name="role" value="cliente" class="peer sr-only" @checked(old('role', $selectedRole) === 'cliente')>
                                                <span class="block rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition peer-checked:border-brand-400 peer-checked:bg-brand-50 peer-checked:ring-2 peer-checked:ring-brand-200">
                                                    <span class="flex items-center gap-3">
                                                        <span class="grid h-11 w-11 place-items-center rounded-lg bg-brand-100 text-brand-700">
                                                            <x-icon name="book-open" class="h-6 w-6" />
                                                        </span>
                                                        <span>
                                                            <span class="block font-medium text-slate-900">Lector</span>
                                                            <span class="mt-1 block text-sm text-slate-600">Guardar libros, leer en línea y publicar reseñas.</span>
                                                        </span>
                                                    </span>
                                                </span>
                                            </label>

                                            <label class="block cursor-pointer">
                                                <input type="radio" name="role" value="autor" class="peer sr-only" @checked(old('role', $selectedRole) === 'autor')>
                                                <span class="block rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition peer-checked:border-brand-400 peer-checked:bg-brand-50 peer-checked:ring-2 peer-checked:ring-brand-200">
                                                    <span class="flex items-center gap-3">
                                                        <span class="grid h-11 w-11 place-items-center rounded-lg bg-brand-100 text-brand-700">
                                                            <x-icon name="cloud-arrow-up" class="h-6 w-6" />
                                                        </span>
                                                        <span>
                                                            <span class="block font-medium text-slate-900">Autor</span>
                                                            <span class="mt-1 block text-sm text-slate-600">Publicar libros y administrar su catálogo desde el panel.</span>
                                                        </span>
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                        @error('role')
                                            <p class="error-text">{{ $message }}</p>
                                        @enderror
                                    </fieldset>

                                    <button type="submit" class="btn btn-primary btn-block">Crear cuenta</button>
                                </form>

                                <p class="mt-8 text-sm text-slate-600">
                                    ¿Ya tienes cuenta?
                                    <a href="{{ route('login') }}" class="link">Inicia sesión</a>.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
