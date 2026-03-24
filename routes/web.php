<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Web
|--------------------------------------------------------------------------
|
| Aquí se definen las rutas públicas (las que cualquier persona puede ver
| sin estar logueado). La landing page es la ruta principal '/'.
|
*/

// Ruta principal — muestra la landing page del negocio
Route::view('/', 'landing')->name('landing');
