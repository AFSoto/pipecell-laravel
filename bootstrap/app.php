<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        // Canales de tiempo real (Pusher) y su ruta de permiso POST /broadcasting/auth
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
        // Webhooks: fuera del grupo "web" (sin sesión, cookies ni CSRF), con un
        // límite de llamadas por minuto por si alguien intenta saturarlos
        then: function () {
            Route::middleware('throttle:120,1')->group(__DIR__ . '/../routes/webhooks.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn() => route('login'));
        $middleware->redirectUsersTo(fn() => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
