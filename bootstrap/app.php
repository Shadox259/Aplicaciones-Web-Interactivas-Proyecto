<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias usado en las rutas: ->middleware('role:admin,autor')
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        // Cierra la sesion de cuentas que el administrador haya desactivado.
        $middleware->web(append: [
            EnsureAccountIsActive::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('inicio'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Datos de tarjeta: nunca se guardan en la sesion ni se vuelven a mostrar tras un error de validacion.
        $exceptions->dontFlash(['card_name', 'card_number', 'card_expiry', 'card_cvv']);

        // La API (/api/*) siempre responde JSON, incluso en errores de validacion o 404.
        $exceptions->shouldRenderJsonWhen(
            fn ($request, $e) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
