<?php

namespace App\Events;

use App\Support\TiempoReal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Aviso en tiempo real: cambiaron las cajas (se creó una nueva). Las pantallas piden
 * las cajas libres a GET /admin/reparaciones/sincronizar para el formulario de
 * nueva reparación.
 *
 * Ocupar o liberar una caja no usa este aviso: llega con ReparacionCambio (crear o
 * cambiar de estado una reparación), que también trae las cajas libres al sincronizar.
 */
class CajasCambio implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public function broadcastOn(): array
    {
        return [new PrivateChannel(TiempoReal::CANAL)];
    }

    public function broadcastAs(): string
    {
        return 'cajas.cambio';
    }

    public function broadcastWith(): array
    {
        return [];
    }
}
