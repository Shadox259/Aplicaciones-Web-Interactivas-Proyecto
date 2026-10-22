@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
    <div class="container-page py-10">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="grid lg:grid-cols-[0.95fr_1.05fr]">
                <section class="bg-brand-900 px-8 py-12 text-white sm:px-12">
                    <span class="badge bg-white/10 text-brand-200 ring-1 ring-white/15">Acceso</span>
                    <h1 class="mt-6 text-3xl font-semibold text-white">Ingresa a tu cuenta</h1>
                    <p class="mt-4 max-w-xl text-sm leading-7 text-brand-100 sm:text-base">
                        Accede a tus libros guardados, a tus reseñas y, si corresponde, a las herramientas de publicación.
                    </p>

                    <ul class="mt-8 space-y-4 text-sm text-brand-100">
                        <li class="flex gap-3"><x-icon name="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-brand-200" /> Guarda libros para tenerlos a mano en tu biblioteca personal.</li>
                        <li class="flex gap-3"><x-icon name="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-brand-200" /> Lee y descarga los libros publicados desde cualquier dispositivo.</li>
                        <li class="flex gap-3"><x-icon name="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-brand-200" /> Los autores y administradores pueden gestionar catálogos desde el panel.</li>
                    </ul>
                </section>

                <section class="bg-surface px-6 py-10 sm:px-10 lg:px-12" x-data="{ showPassword: false, email: @js(old('email')), password: '' }">
                    <div class="mx-auto max-w-xl">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-6">
                                    <h2 class="text-2xl font-semibold text-slate-900">Iniciar sesión</h2>
                                    <p class="mt-2 text-sm text-slate-600">Usa tu correo y contraseña para continuar.</p>
                                </div>

                                <form action="{{ route('login.store') }}" method="POST" class="space-y-5">
                                    @csrf

                                    <x-field name="email" label="Correo electrónico" type="email" autocomplete="email" required x-model="email" />

                                    <div>
                                        <label for="password" class="label">Contraseña<span class="text-red-500"> *</span></label>
                                        <div class="relative">
                                            <input id="password" :type="showPassword ? 'text' : 'password'" name="password" autocomplete="current-password" required x-model="password"
                                                   class="input pr-12 @error('password') input-error @enderror">
                                            <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-3 text-slate-500 hover:text-slate-700" :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                                                <x-icon name="eye" class="h-5 w-5" x-show="!showPassword" />
                                                <x-icon name="eye-slash" class="h-5 w-5" x-show="showPassword" x-cloak />
                                            </button>
                                        </div>
                                        @error('password')
                                            <p class="error-text">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <label class="inline-flex items-center gap-3 text-sm text-slate-600">
                                        <input type="checkbox" name="remember" value="1" class="checkbox" @checked(old('remember'))>
                                        <span>Recuérdame</span>
                                    </label>

                                    <button type="submit" class="btn btn-primary btn-block">Entrar</button>
                                </form>

                                @if (config('wonderland.show_demo_accounts'))
                                    <div class="mt-8 rounded-lg border border-dashed border-brand-300 bg-brand-50 p-5">
                                        <div class="flex items-center gap-2">
                                            <x-icon name="information-circle" class="h-5 w-5 text-brand-700" />
                                            <h3 class="font-medium text-brand-900">Cuentas de demostración</h3>
                                        </div>
                                        <div class="mt-4 space-y-3">
                                            @foreach (config('wonderland.demo_accounts', []) as $account)
                                                <div class="flex flex-col gap-3 rounded-lg bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                                                    <div>
                                                        <p class="font-medium text-slate-900">{{ $account['role'] }}</p>
                                                        <p class="text-sm text-slate-600">{{ $account['email'] }}</p>
                                                    </div>
                                                    <button type="button" class="btn btn-outline btn-sm" @click="email = '{{ $account['email'] }}'; password = '{{ $account['password'] }}'">Usar</button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <p class="mt-8 text-sm text-slate-600">
                                    ¿Aún no tienes cuenta?
                                    <a href="{{ route('registro') }}" class="link">Crear una cuenta</a>.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
