<?php

namespace App\Events;

use App\Support\TiempoReal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Str;

/**
 * Aviso en tiempo real: cambió una reparación.
 *
 * Por privacidad no lleva datos del cliente (Pusher es un servicio externo): solo
 * qué reparación cambió, qué pasó y quién lo hizo. Cada pantalla pide la fila
 * actualizada a GET /admin/reparaciones/sincronizar.
 *
 * Se envía con TiempoReal::avisar(), nunca directo con event().
 */
class ReparacionCambio implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public const CREADA      = 'creada';
    public const ACTUALIZADA = 'actualizada';
    public const ESTADO      = 'estado';
    public const ABONO       = 'abono';
    public const WHATSAPP    = 'whatsapp';

    /** Primer nombre de quien hizo el cambio (null si fue el webhook de Meta). */
    public ?string $autor;

    /**
     * @param  string|null  $estado  Estado nuevo, solo cuando $accion es ESTADO
     */
    public function __construct(
        public int $reparacionId,
        public string $accion,
        public ?string $estado = null,
        ?string $autor = null,
    ) {
        $nombre      = $autor ?? auth()->user()?->nombre;
        $this->autor = $nombre !== null ? Str::before(trim($nombre), ' ') : null;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel(TiempoReal::CANAL)];
    }

    public function broadcastAs(): string
    {
        return 'reparacion.cambio';
    }

    public function broadcastWith(): array
    {
        return [
            'id'     => $this->reparacionId,
            'accion' => $this->accion,
            'estado' => $this->estado,
            'autor'  => $this->autor,
        ];
    }
}
