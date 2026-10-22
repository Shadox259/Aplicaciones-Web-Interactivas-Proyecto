<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · Panel · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>
    @stack('head')
</head>
<body class="h-full bg-surface">
@php
    $user = auth()->user();
    $isAdmin = $user->isAdmin();
    $item = fn (bool $active) => 'flex items-center gap-3 border-l-2 px-3 py-2.5 text-sm font-medium transition '
        .($active ? 'border-brand-300 bg-white/10 text-white' : 'border-transparent text-slate-300 hover:bg-white/5 hover:text-white');
@endphp
<div x-data="{ sidebar: false }" class="flex min-h-full">
    {{-- Fondo oscuro en movil --}}
    <div x-cloak x-show="sidebar" x-transition.opacity @click="sidebar = false" class="fixed inset-0 z-40 bg-slate-900/60 lg:hidden"></div>

    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 flex w-64 shrink-0 flex-col bg-slate-900 transition-transform duration-200 lg:static lg:translate-x-0">
        <a href="{{ route('panel.dashboard') }}" class="flex h-16 items-center gap-2.5 border-b border-white/10 px-5">
            <span class="grid h-9 w-9 place-items-center rounded-md bg-brand-700 text-white"><x-icon name="book-open" class="h-5 w-5" /></span>
            <span class="text-base font-semibold leading-tight text-white">Library of Wonderland</span>
        </a>

        <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-5" aria-label="Panel">
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $isAdmin ? 'Administración' : 'Panel de autor' }}</p>
            <a href="{{ route('panel.dashboard') }}" class="{{ $item(request()->routeIs('panel.dashboard')) }}"><x-icon name="squares-2x2" class="h-5 w-5" /> Resumen</a>
            <a href="{{ route('panel.libros.index') }}" class="{{ $item(request()->routeIs('panel.libros.index', 'panel.libros.edit')) }}"><x-icon name="rectangle-stack" class="h-5 w-5" /> {{ $isAdmin ? 'Todos los libros' : 'Mis libros' }}</a>
            <a href="{{ route('panel.libros.create') }}" class="{{ $item(request()->routeIs('panel.libros.create')) }}"><x-icon name="cloud-arrow-up" class="h-5 w-5" /> Subir libro (PDF)</a>

            @if ($isAdmin)
                <p class="px-3 pb-2 pt-5 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Gestión</p>
                <a href="{{ route('panel.usuarios.index') }}" class="{{ $item(request()->routeIs('panel.usuarios.*')) }}"><x-icon name="users" class="h-5 w-5" /> Usuarios</a>
                <a href="{{ route('panel.generos.index') }}" class="{{ $item(request()->routeIs('panel.generos.*')) }}"><x-icon name="tag" class="h-5 w-5" /> Géneros</a>
            @endif

            <p class="px-3 pb-2 pt-5 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Cuenta</p>
            <a href="{{ route('perfil.edit') }}" class="{{ $item(request()->routeIs('perfil.*')) }}"><x-icon name="user-circle" class="h-5 w-5" /> Mi perfil</a>
            <a href="{{ route('inicio') }}" class="{{ $item(false) }}"><x-icon name="home" class="h-5 w-5" /> Ver el sitio</a>
            <a href="{{ route('biblioteca') }}" class="{{ $item(request()->routeIs('biblioteca')) }}"><x-icon name="bookmark" class="h-5 w-5" /> Mi biblioteca</a>
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-700 text-sm font-semibold text-white">{{ $user->initials() }}</span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-white">{{ $user->name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ $user->roleLabel() }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-md bg-white/10 px-3 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/20">
                    <x-icon name="arrow-right-on-rectangle" class="h-4 w-4" /> Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 lg:hidden">
            <button type="button" @click="sidebar = true" class="rounded-md p-2 text-slate-600 hover:bg-slate-100" aria-label="Abrir menú"><x-icon name="bars-3" class="h-6 w-6" /></button>
            <span class="text-base font-semibold text-slate-900">@yield('title', 'Panel')</span>
        </header>

        <main class="flex-1 px-4 py-8 sm:px-6 lg:px-10">
            <div class="mx-auto max-w-6xl">
                <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-semibold sm:text-3xl">@yield('title', 'Panel')</h1>
                        @hasSection('subtitle')
                            <p class="mt-1 text-sm text-slate-500">@yield('subtitle')</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2">@yield('actions')</div>
                </div>

                <x-flash />

                @yield('content')
            </div>
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
