<?php

use App\Models\User;
use App\Support\TiempoReal;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Canales de tiempo real (Pusher)
|--------------------------------------------------------------------------
|
| Los canales privados piden permiso a POST /broadcasting/auth antes de
| escuchar. Solo entran usuarios con sesión y activos.
|
*/

// Avisos del taller: cambios en reparaciones y cajas
Broadcast::channel(TiempoReal::CANAL, fn (User $user) => $user->estado === 'activo');
