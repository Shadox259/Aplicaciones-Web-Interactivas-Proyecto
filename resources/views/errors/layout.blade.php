<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · {{ config('app.name', 'Library of Wonderland') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="grid min-h-full place-items-center bg-brand-900 px-6 py-12 text-center">
    <main class="max-w-lg">
        <p class="text-8xl font-semibold text-brand-200 sm:text-9xl">@yield('code')</p>
        <h1 class="mt-4 text-3xl font-semibold text-white">@yield('heading')</h1>
        <p class="mt-3 text-brand-200">@yield('message')</p>
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ url('/') }}" class="btn btn-primary btn-lg">Volver al inicio</a>
            <a href="javascript:history.back()" class="btn btn-lg border border-white/30 text-white hover:bg-white/10">Regresar</a>
        </div>
        <p class="mt-10 text-xs text-brand-300">Library of Wonderland</p>
    </main>
</body>
</html>
