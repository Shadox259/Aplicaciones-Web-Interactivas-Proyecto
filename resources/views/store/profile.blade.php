{{-- Autores y administradores ven su perfil dentro del panel (con la barra lateral); los clientes, en el sitio. --}}
@extends(auth()->user()->canPublish() ? 'layouts.panel' : 'layouts.app')

@section('title', 'Mi perfil')
@section('subtitle', 'Actualiza tus datos, cambia tu contraseña y administra el estado de tu cuenta.')

@section('content')
    @php($inPanel = $user->canPublish())
    <div @class(['container-page py-10' => ! $inPanel])>
        @unless ($inPanel)
            <div class="flex flex-col gap-2">
                <p class="text-xs uppercase tracking-wider text-slate-500">Cuenta</p>
                <h1 class="text-2xl font-semibold text-slate-900">Mi perfil</h1>
                <p class="text-sm text-slate-600">@yield('subtitle')</p>
            </div>
        @endunless

        <div @class(['grid gap-8 xl:grid-cols-[1.1fr_0.9fr]', 'mt-8' => ! $inPanel])>
            <section class="space-y-8">
                <article class="card">
                    <div class="card-body">
                        <h2 class="text-xl font-semibold text-slate-900">Datos de la cuenta</h2>
                        <form action="{{ route('perfil.update') }}" method="POST" class="mt-6 space-y-5">
                            @csrf
                            @method('PUT')

                            <x-field name="name" label="Nombre completo" :value="old('name', $user->name)" required />
                            <x-field name="email_view" label="Correo electrónico" :value="$user->email" disabled />

                            @if ($user->canPublish())
                                <x-field name="bio" label="Biografía" type="textarea" :value="old('bio', $user->bio)" rows="5" help="Se mostrará en tu perfil público de autor." />
                            @endif

                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        </form>
                    </div>
                </article>

                <article class="card">
                    <div class="card-body">
                        <h2 class="text-xl font-semibold text-slate-900">Cambiar contraseña</h2>
                        <form action="{{ route('perfil.password') }}" method="POST" class="mt-6 space-y-5">
                            @csrf
                            @method('PUT')

                            <x-field name="current_password" label="Contraseña actual" type="password" required autocomplete="current-password" />
                            <x-field name="password" label="Nueva contraseña" type="password" required autocomplete="new-password" />
                            <x-field name="password_confirmation" label="Confirmar nueva contraseña" type="password" required autocomplete="new-password" />

                            <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
                        </form>
                    </div>
                </article>

                <article class="card border-red-200">
                    <div class="card-body">
                        <h2 class="text-xl font-semibold text-red-700">Zona de peligro</h2>
                        <p class="mt-3 text-sm text-slate-600">Desactivar tu cuenta cerrará tu sesión actual. Tus datos y tus libros publicados se conservarán para que un administrador pueda reactivarte si lo solicitas.</p>

                        <form action="{{ route('perfil.destroy') }}" method="POST" class="mt-6 space-y-5" onsubmit="return confirm('¿Deseas desactivar tu cuenta?')">
                            @csrf
                            @method('DELETE')

                            <x-field name="password" label="Confirma tu contraseña para desactivar la cuenta" type="password" required autocomplete="current-password" />
                            <button type="submit" class="btn btn-danger">Desactivar mi cuenta</button>
                        </form>
                    </div>
                </article>
            </section>

            <aside class="space-y-6">
                <article class="card">
                    <div class="card-body">
                        <h2 class="text-xl font-semibold text-slate-900">Resumen</h2>
                        <div class="mt-5 space-y-4">
                            <div class="rounded-lg bg-slate-50 p-4">
                                <p class="text-xs uppercase tracking-wider text-slate-500">Rol</p>
                                <span class="badge badge-brand mt-2">{{ $user->roleLabel() }}</span>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-4">
                                <p class="text-xs uppercase tracking-wider text-slate-500">Miembro desde</p>
                                <p class="mt-1 font-medium text-slate-900">{{ $user->created_at->translatedFormat('d \\d\\e F \\d\\e Y') }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-4">
                                <p class="text-xs uppercase tracking-wider text-slate-500">Libros guardados</p>
                                <p class="mt-1 text-3xl font-semibold text-slate-900">{{ $libraryCount }}</p>
                            </div>
                            @if ($user->canPublish())
                                <div class="rounded-lg bg-slate-50 p-4">
                                    <p class="text-xs uppercase tracking-wider text-slate-500">Libros publicados</p>
                                    <p class="mt-1 text-3xl font-semibold text-slate-900">{{ $publishedCount }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </article>
            </aside>
        </div>
    </div>
@endsection
