<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CajaController;
use App\Http\Controllers\Admin\ReparacionController;
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




// ── 4. Rutas del panel admin (protegidas) ──
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {

    // Dashboard — controlador dedicado con datos reales de la BD
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/ingresos-anuales', [DashboardController::class, 'ingresosAnuales'])->name('dashboard.ingresos-anuales');

    // ── Reparaciones ──
    Route::get('/reparaciones', [ReparacionController::class, 'index'])
        ->name('reparaciones.index');

    Route::post('/reparaciones', [ReparacionController::class, 'store'])
        ->name('reparaciones.store');

    Route::patch('/reparaciones/{reparacion}/estado', [ReparacionController::class, 'actualizarEstado'])
        ->name('reparaciones.actualizarEstado');

    Route::post('/reparaciones/{reparacion}/abono', [ReparacionController::class, 'registrarAbono'])
        ->name('reparaciones.registrarAbono');

    // ── Edición de reparación ──
    // GET  → muestra el formulario con los datos actuales pre-llenados
    // PUT  → recibe el formulario y persiste los cambios
    

    Route::put('/reparaciones/{reparacion}', [ReparacionController::class, 'update'])
        ->name('reparaciones.update');

    // ── Cajas ──
    Route::post('/cajas', [CajaController::class, 'store'])
        ->name('cajas.store');
});
