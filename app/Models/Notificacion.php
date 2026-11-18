<?php

namespace App\Models;

use App\Enums\EstadoNotificacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Intento de aviso al cliente sobre su reparación.
 *
 * Se crea un registro por cada intento (enviado, fallido u omitido), así la
 * plataforma puede mostrar si al cliente se le avisó y, si no, por qué.
 */
class Notificacion extends Model
{
    protected $table = 'notificaciones';

    public const CANAL_WHATSAPP = 'whatsapp';

    public const TIPO_REPARACION_LISTA = 'reparacion_lista';

    protected $fillable = [
        'reparacion_id',
        'user_id',
        'canal',
        'tipo',
        'telefono',
        'estado',
        'mensaje_id',
        'detalle',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoNotificacion::class,
        ];
    }

    // ── Relaciones ──

    public function reparacion(): BelongsTo
    {
        return $this->belongsTo(Reparacion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Helpers ──

    /**
     * Frase para el aviso flotante y el tooltip del indicador.
     * Ejemplo: "WhatsApp enviado a 310 123 4567"
     */
    public function resumen(): string
    {
        $canal = $this->nombreCanal();

        return match ($this->estado) {
            EstadoNotificacion::Enviado => "{$canal} enviado a {$this->telefono}",
            EstadoNotificacion::Fallido => "No se pudo enviar el {$canal}: {$this->detalle}",
            EstadoNotificacion::Omitido => "No se envió {$canal}: {$this->detalle}",
        };
    }

    /**
     * Datos que necesita la vista para pintar el indicador de la fila.
     * Se usa tanto en el render inicial (data-whatsapp) como en las respuestas AJAX.
     */
    public function paraVista(): array
    {
        $canal = $this->nombreCanal();

        return [
            'estado'   => $this->estado->value,
            'etiqueta' => match ($this->estado) {
                EstadoNotificacion::Enviado => "{$canal} ✓ " . $this->created_at->format('d/m h:i A'),
                EstadoNotificacion::Fallido => "{$canal} ✗ falló",
                EstadoNotificacion::Omitido => "{$canal} no enviado",
            },
            'mensaje'  => $this->resumen(),
        ];
    }

    private function nombreCanal(): string
    {
        return match ($this->canal) {
            self::CANAL_WHATSAPP => 'WhatsApp',
            default              => strtoupper($this->canal),
        };
    }
}
