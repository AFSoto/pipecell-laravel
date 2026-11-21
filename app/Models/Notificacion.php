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
 * En los enviados, el webhook de Meta llena entregado_at y leido_at.
 *
 * Todos los textos que ve el usuario sobre los avisos salen de aquí
 * (resumen() y paraVista()), para que no se repitan en otros lugares.
 */
class Notificacion extends Model
{
    protected $table = 'notificaciones';

    public const CANAL_WHATSAPP = 'whatsapp';

    public const TIPO_REPARACION_LISTA = 'reparacion_lista';

    /** Detalle de un intento que falló por un error de la plataforma (no de Meta). */
    public const DETALLE_ERROR_INTERNO = 'error interno de la plataforma. Intenta de nuevo.';

    protected $fillable = [
        'reparacion_id',
        'user_id',
        'canal',
        'tipo',
        'telefono',
        'estado',
        'mensaje_id',
        'entregado_at',
        'leido_at',
        'detalle',
    ];

    protected function casts(): array
    {
        return [
            'estado'       => EstadoNotificacion::class,
            'entregado_at' => 'datetime',
            'leido_at'     => 'datetime',
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
     * Hasta dónde llegó un aviso enviado según Meta: 'leido', 'entregado' o null
     * (todavía sin confirmar, o el aviso no es un enviado).
     */
    public function estadoDeEntrega(): ?string
    {
        if ($this->estado !== EstadoNotificacion::Enviado) {
            return null;
        }

        return match (true) {
            $this->leido_at !== null     => 'leido',
            $this->entregado_at !== null => 'entregado',
            default                      => null,
        };
    }

    /**
     * Frase para el aviso flotante y el tooltip del indicador.
     * Ejemplo: "WhatsApp enviado a 310 123 4567"
     */
    public function resumen(): string
    {
        $canal = $this->nombreCanal();

        return match ($this->estado) {
            EstadoNotificacion::Enviado => match ($this->estadoDeEntrega()) {
                'leido'     => "{$canal} leído por el cliente ({$this->telefono}) el " . $this->leido_at->format('d/m h:i A'),
                'entregado' => "{$canal} entregado a {$this->telefono} el " . $this->entregado_at->format('d/m h:i A'),
                default     => "{$canal} enviado a {$this->telefono}",
            },
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
            'entrega'  => $this->estadoDeEntrega(),
            // Por qué falló o no se envió (null en los enviados)
            'motivo'   => $this->estado === EstadoNotificacion::Enviado ? null : $this->detalle,
            'etiqueta' => match ($this->estado) {
                EstadoNotificacion::Enviado => match ($this->estadoDeEntrega()) {
                    'leido'     => "{$canal} ✓✓ leído",
                    'entregado' => "{$canal} ✓✓ entregado",
                    default     => "{$canal} ✓ " . $this->created_at->format('d/m h:i A'),
                },
                EstadoNotificacion::Fallido => "{$canal} ✗ falló",
                EstadoNotificacion::Omitido => "{$canal} no enviado",
            },
            'mensaje'  => $this->resumen(),
        ];
    }

    /**
     * Datos para la vista cuando el aviso falló por un error interno y ni siquiera
     * se pudo guardar el intento (por ejemplo, la base de datos no respondió).
     */
    public static function vistaErrorInterno(string $canal = self::CANAL_WHATSAPP): array
    {
        return (new self([
            'canal'   => $canal,
            'estado'  => EstadoNotificacion::Fallido,
            'detalle' => self::DETALLE_ERROR_INTERNO,
        ]))->paraVista();
    }

    private function nombreCanal(): string
    {
        return match ($this->canal) {
            self::CANAL_WHATSAPP => 'WhatsApp',
            default              => strtoupper($this->canal),
        };
    }
}
