@extends('layouts.panel')

@section('title', 'Usuarios')

@section('subtitle')
    Crea cuentas nuevas, administra roles y controla el estado de acceso sin perder la relación entre los libros y sus creadores.
@endsection

@section('content')
    <div class="space-y-6">
        <section class="card" x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">
            <div class="card-body">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Nuevo usuario</h2>
                        <p class="mt-1 text-sm text-slate-500">Crea cuentas para administradores, autores o lectores.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" @click="open = ! open">
                        <x-icon name="plus" class="h-4 w-4" x-show="! open" />
                        <x-icon name="x-mark" class="h-4 w-4" x-show="open" x-cloak />
                        <span x-text="open ? 'Cerrar formulario' : 'Agregar usuario'"></span>
                    </button>
                </div>

                <form x-show="open" x-cloak method="POST" action="{{ route('panel.usuarios.store') }}" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @csrf
                    <x-field name="name" label="Nombre" :value="old('name')" required />
                    <x-field name="email" label="Correo electrónico" type="email" :value="old('email')" required />
                    <x-field name="password" label="Contraseña" type="password" required />
                    <x-field name="password_confirmation" label="Confirmar contraseña" type="password" required />

                    <x-field name="role" label="Rol" type="select" required>
                        @foreach ($roles as $key => $label)
                            <option value="{{ $key }}" @selected(old('role') === $key)>{{ $label }}</option>
                        @endforeach
                    </x-field>

                    <div class="flex items-end md:col-span-2 xl:col-span-3">
                        <button type="submit" class="btn btn-primary">
                            <x-icon name="check" class="h-5 w-5" />
                            Crear usuario
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('panel.usuarios.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label for="q" class="label">Buscar</label>
                        <input id="q" type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Nombre o correo">
                    </div>
                    <div>
                        <label for="rol" class="label">Rol</label>
                        <select id="rol" name="rol" class="input">
                            <option value="">Todos</option>
                            @foreach ($roles as $key => $label)
                                <option value="{{ $key }}" @selected(($filters['rol'] ?? '') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="estado" class="label">Estado</label>
                        <select id="estado" name="estado" class="input">
                            <option value="">Todos</option>
                            <option value="activos" @selected(($filters['estado'] ?? '') === 'activos')>Activos</option>
                            <option value="inactivos" @selected(($filters['estado'] ?? '') === 'inactivos')>Inactivos</option>
                        </select>
                    </div>
                    <div class="flex flex-wrap items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <x-icon name="funnel" class="h-5 w-5" />
                            Filtrar
                        </button>
                        <a href="{{ route('panel.usuarios.index') }}" class="btn btn-outline">Limpiar</a>
                    </div>
                </form>
            </div>
        </section>

        @if ($users->isEmpty())
            <section class="card">
                <div class="card-body py-14 text-center">
                    <x-icon name="users" class="mx-auto h-12 w-12 text-slate-300" />
                    <h2 class="mt-4 text-xl font-semibold text-slate-900">No se encontraron usuarios</h2>
                    <p class="mt-2 text-sm text-slate-500">Prueba con otros filtros o crea una nueva cuenta desde el formulario superior.</p>
                </div>
            </section>
        @else
            <section class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Libros</th>
                        <th>Registro</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($users as $user)
                        @php
                            $isSelf = $currentUser->is($user);
                        @endphp
                        <tr class="{{ $user->is_active ? '' : 'bg-slate-50/70' }}">
                            <td>
                                <div class="flex min-w-[220px] items-center gap-3">
                                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full {{ $user->is_active ? 'bg-brand-100 text-brand-800' : 'bg-slate-200 text-slate-500' }} text-sm font-semibold">
                                        {{ $user->initials() }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold {{ $user->is_active ? 'text-slate-900' : 'text-slate-500' }}">
                                            {{ $user->name }}@if ($isSelf) <span class="font-normal text-slate-400">(tú)</span>@endif
                                        </p>
                                        <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($isSelf)
                                    <span class="badge badge-brand">{{ $user->roleLabel() }}</span>
                                @else
                                    <form method="POST" action="{{ route('panel.usuarios.update', $user) }}"
                                          x-data="{ role: '{{ $user->role }}', initial: '{{ $user->role }}' }" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $user->is_active ? 1 : 0 }}">
                                        <select name="role" x-model="role" class="input w-36 py-1.5" aria-label="Rol de {{ $user->name }}">
                                            @foreach ($roles as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" x-cloak x-show="role !== initial" class="btn btn-primary btn-sm">Guardar</button>
                                    </form>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $user->is_active ? 'badge-green' : 'badge-red' }}">
                                    {{ $user->is_active ? 'Activo' : 'Desactivado' }}
                                </span>
                            </td>
                            <td class="text-slate-700">{{ $user->books_count }}</td>
                            <td class="whitespace-nowrap text-slate-500">{{ $user->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div class="flex justify-end">
                                    @if ($isSelf)
                                        <span class="text-xs text-slate-400">Tu cuenta</span>
                                    @elseif ($user->is_active)
                                        <form method="POST" action="{{ route('panel.usuarios.destroy', $user) }}"
                                              onsubmit="return confirm('Se eliminará al usuario: su cuenta quedará desactivada y ya no podrá iniciar sesión. Sus libros se conservan con su nombre como creador. ¿Deseas continuar?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger-outline btn-sm">
                                                <x-icon name="trash" class="h-4 w-4" /> Eliminar
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('panel.usuarios.update', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="role" value="{{ $user->role }}">
                                            <input type="hidden" name="is_active" value="1">
                                            <button type="submit" class="btn btn-outline btn-sm">
                                                <x-icon name="arrow-path" class="h-4 w-4" /> Reactivar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </section>

            <div>
                {{ $users->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
