<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inicio') · {{ config('app.name') }}</title>
    <meta name="description" content="@yield('description', 'Library of Wonderland: biblioteca digital gratuita donde los autores publican sus libros en PDF y los lectores los leen, descargan y guardan.')">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>
    @stack('head')
</head>
<body class="flex min-h-full flex-col">
@php
    $user = auth()->user();
    $navLink = fn (bool $active) => 'inline-flex items-center border-b-2 px-3 text-sm font-medium transition '.($active ? 'border-brand-700 text-brand-900' : 'border-transparent text-slate-600 hover:border-slate-300 hover:text-slate-900');
    $mobileLink = fn (bool $active) => 'rounded-md px-3 py-2 text-sm font-medium '.($active ? 'bg-brand-50 text-brand-900' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900');
@endphp

<a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:shadow-lg">Saltar al contenido</a>

<header x-data="{ menu: false }" class="sticky top-0 z-40 border-b border-slate-200 bg-white">
    <div class="container-page flex h-16 items-center gap-3">
        <a href="{{ route('inicio') }}" class="flex shrink-0 items-center gap-2.5" aria-label="Library of Wonderland, inicio">
            <span class="grid h-9 w-9 place-items-center rounded-md bg-brand-800 text-white">
                <x-icon name="book-open" class="h-5 w-5" />
            </span>
            <span class="text-lg font-semibold leading-none tracking-tight text-slate-900">Library of Wonderland</span>
        </a>

        <nav class="ml-8 hidden h-16 items-stretch gap-1 lg:flex" aria-label="Principal">
            <a href="{{ route('inicio') }}" class="{{ $navLink(request()->routeIs('inicio')) }}">Inicio</a>
            <a href="{{ route('catalogo') }}" class="{{ $navLink(request()->routeIs('catalogo', 'libros.*', 'autores.*')) }}">Catálogo</a>
            @if ($user)
                <a href="{{ route('biblioteca') }}" class="{{ $navLink(request()->routeIs('biblioteca', 'leer.*')) }}">Mi biblioteca</a>
            @endif
            @if ($user && $user->canPublish())
                <a href="{{ route('panel.dashboard') }}" class="{{ $navLink(false) }}">
                    {{ $user->isAdmin() ? 'Administración' : 'Panel de autor' }}
                </a>
            @endif
        </nav>

        <form action="{{ route('catalogo') }}" method="GET" role="search" class="ml-auto hidden max-w-xs flex-1 md:block">
            <label for="buscar-nav" class="sr-only">Buscar libros</label>
            <div class="relative">
                <x-icon name="magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input id="buscar-nav" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar título o autor…"
                       class="w-full rounded-md border border-slate-300 bg-white py-2 pl-9 pr-4 text-sm placeholder:text-slate-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
            </div>
        </form>

        <div class="ml-auto flex items-center gap-1.5 md:ml-0">
            @auth
                <div x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false" class="relative">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="true"
                            class="flex items-center gap-2 rounded-md py-1 pl-1 pr-2 transition hover:bg-slate-100">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-brand-700 text-xs font-semibold text-white">{{ $user->initials() }}</span>
                        <span class="hidden max-w-32 truncate text-sm font-medium text-slate-700 sm:block">{{ $user->name }}</span>
                        <x-icon name="chevron-down" class="h-4 w-4 text-slate-400" />
                    </button>
                    <div x-cloak x-show="open" x-transition.origin.top.right
                         class="absolute right-0 mt-2 w-60 overflow-hidden rounded-md border border-slate-200 bg-white py-1 shadow-sm">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                            <span class="badge badge-brand mt-1.5">{{ $user->roleLabel() }}</span>
                        </div>
                        @if ($user->canPublish())
                            <a href="{{ route('panel.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-brand-50"><x-icon name="squares-2x2" class="h-4 w-4" /> {{ $user->isAdmin() ? 'Administración' : 'Panel de autor' }}</a>
                        @endif
                        @if ($user)
                            <a href="{{ route('biblioteca') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-brand-50"><x-icon name="book-open" class="h-4 w-4" /> Mi biblioteca</a>
                        @endif
                        <a href="{{ route('perfil.edit') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-brand-50"><x-icon name="user-circle" class="h-4 w-4" /> Mi perfil</a>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50"><x-icon name="arrow-right-on-rectangle" class="h-4 w-4" /> Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm hidden sm:inline-flex">Iniciar sesión</a>
                <a href="{{ route('registro') }}" class="btn btn-primary btn-sm">Crear cuenta</a>
            @endauth

            <button type="button" @click="menu = !menu" class="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden" :aria-expanded="menu" aria-label="Abrir menú">
                <x-icon name="bars-3" class="h-6 w-6" x-show="!menu" />
                <x-icon name="x-mark" class="h-6 w-6" x-show="menu" x-cloak />
            </button>
        </div>
    </div>

    <div x-cloak x-show="menu" x-transition class="border-t border-slate-100 bg-white lg:hidden">
        <div class="container-page space-y-1 py-3">
            <form action="{{ route('catalogo') }}" method="GET" role="search" class="pb-2">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar título o autor…" class="input">
            </form>
            <a href="{{ route('inicio') }}" class="block {{ $mobileLink(request()->routeIs('inicio')) }}">Inicio</a>
            <a href="{{ route('catalogo') }}" class="block {{ $mobileLink(request()->routeIs('catalogo')) }}">Catálogo</a>
            @if ($user)
                <a href="{{ route('biblioteca') }}" class="block {{ $mobileLink(request()->routeIs('biblioteca')) }}">Mi biblioteca</a>
            @endif
            @if ($user && $user->canPublish())
                <a href="{{ route('panel.dashboard') }}" class="block {{ $mobileLink(false) }}">{{ $user->isAdmin() ? 'Administración' : 'Panel de autor' }}</a>
            @endif
            @guest
                <a href="{{ route('login') }}" class="block {{ $mobileLink(request()->routeIs('login')) }}">Iniciar sesión</a>
            @endguest
        </div>
    </div>
</header>

<main id="contenido" class="flex-1">
    @hasSection('flash')
        {{-- Las paginas pueden colocar los mensajes en otro lugar con @section('flash') --}}
        @yield('flash')
    @else
        @if (session()->hasAny(['success', 'error', 'warning', 'info']))
            <div class="container-page pt-6"><x-flash /></div>
        @endif
    @endif

    @yield('content')
</main>

<footer class="mt-16 bg-slate-900 text-slate-300">
    <div class="container-page grid gap-10 py-12 md:grid-cols-4">
        <div class="md:col-span-2">
            <div class="flex items-center gap-2.5">
                <span class="grid h-9 w-9 place-items-center rounded-md bg-brand-700 text-white"><x-icon name="book-open" class="h-5 w-5" /></span>
                <span class="text-lg font-semibold text-white">Library of Wonderland</span>
            </div>
            <p class="mt-4 max-w-md text-sm leading-relaxed text-slate-400">
                Una biblioteca digital gratuita donde los autores publican sus libros en PDF y los lectores los leen en línea, los descargan y los guardan en su biblioteca personal.
            </p>
        </div>
        <div>
            <h4 class="text-xs font-semibold uppercase tracking-wider text-white">Explorar</h4>
            <ul class="mt-4 space-y-2 text-sm">
                <li><a href="{{ route('catalogo') }}" class="hover:text-white">Catálogo completo</a></li>
                <li><a href="{{ route('catalogo', ['orden' => 'valorados']) }}" class="hover:text-white">Mejor valorados</a></li>
                <li><a href="{{ route('catalogo', ['orden' => 'recientes']) }}" class="hover:text-white">Novedades</a></li>
            </ul>
        </div>
        <div>
            <h4 class="text-xs font-semibold uppercase tracking-wider text-white">Tu cuenta</h4>
            <ul class="mt-4 space-y-2 text-sm">
                @auth
                    <li><a href="{{ route('perfil.edit') }}" class="hover:text-white">Mi perfil</a></li>
                    @if ($user)
                        <li><a href="{{ route('biblioteca') }}" class="hover:text-white">Mi biblioteca</a></li>
                    @endif
                    @if ($user->canPublish())
                        <li><a href="{{ route('panel.dashboard') }}" class="hover:text-white">Panel</a></li>
                    @endif
                @else
                    <li><a href="{{ route('login') }}" class="hover:text-white">Iniciar sesión</a></li>
                    <li><a href="{{ route('registro') }}" class="hover:text-white">Crear cuenta</a></li>
                    <li><a href="{{ route('registro', ['tipo' => 'autor']) }}" class="hover:text-white">Publica tu libro</a></li>
                @endauth
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="container-page flex flex-col items-center justify-between gap-2 py-5 text-xs text-slate-400 sm:flex-row">
            <p>© {{ date('Y') }} Library of Wonderland · Proyecto académico de Aplicaciones Web Escalables</p>
            <p>Hecho con Laravel {{ app()->version() }} y Tailwind CSS</p>
        </div>
    </div>
</footer>
@stack('scripts')
</body>
</html>
