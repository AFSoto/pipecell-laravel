<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Envío de los avisos en tiempo real (Pusher) a las otras pantallas del taller.
 *
 * - Espera a que termine bien la transacción en curso: si se revierte, no avisa nada.
 * - No le avisa a quien hizo el cambio: su pantalla ya lo muestra (toOthers, con el
 *   encabezado X-Socket-ID que mandan los fetch de la vista).
 * - Nunca rompe lo que se estaba guardando: si Pusher falla o tarda (máximo 3 s,
 *   config/broadcasting.php), el error va a laravel.log y la petición sigue normal.
 * - Sin Pusher configurado (BROADCAST_CONNECTION=null) no hace nada: la plataforma
 *   funciona como antes y cada pantalla se actualiza al recargar.
 *
 * El envío es síncrono (ShouldBroadcastNow) porque el hosting no tiene worker de colas.
 */
class TiempoReal
{
    /** Canal privado del taller: lo escuchan solo los usuarios activos (routes/channels.php). */
    public const CANAL = 'taller';

    public static function avisar(object $evento): void
    {
        DB::afterCommit(function () use ($evento) {
            try {
                if (method_exists($evento, 'dontBroadcastToCurrentUser')) {
                    $evento->dontBroadcastToCurrentUser();
                }

                event($evento);
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }
}
