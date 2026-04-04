<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Limitar intentos de login: 5 por minuto por email + 10 por IP
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->input('email'))->response(function () {
                    return back()->withErrors([
                        'email' => 'Demasiados intentos para este correo. Intenta de nuevo en 1 minuto.',
                    ])->onlyInput('email');
                }),
                Limit::perMinute(10)->by($request->ip())->response(function () {
                    return back()->withErrors([
                        'email' => 'Demasiados intentos desde tu dispositivo. Intenta de nuevo en 1 minuto.',
                    ])->onlyInput('email');
                }),
            ];
        });
    }
}
