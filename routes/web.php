<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Web
|--------------------------------------------------------------------------
|
| Rutas organizadas por grupo:
| 1. Públicas — cualquier persona puede acceder
| 2. Auth (guest) — solo usuarios NO logueados (login)
| 3. Admin (auth) — solo usuarios logueados (panel admin)
|
*/

// ── 1. Rutas públicas ──
Route::view('/', 'landing')->name('landing');

// ── 2. Rutas de autenticación (solo para usuarios NO logueados) ──
// El middleware 'guest' impide que un usuario ya logueado vea el login
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// ── 3. Ruta de logout (solo para usuarios logueados) ──
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ── 4. Rutas del panel admin (protegidas con auth) ──
// Todo lo que esté dentro de este grupo requiere estar logueado.
// Si no estás logueado, Laravel te redirige automáticamente a /login
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    // Dashboard temporal — después lo reemplazamos con un controlador real
    Route::get('/dashboard', function () {
    return view('admin.dashboard');
})->name('dashboard');
});
