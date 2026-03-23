<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de abono (pago parcial) de una reparación.
 *
 * Cada vez que un cliente paga algo, se crea un registro aquí.
 * El total abonado se calcula sumando todos los abonos
 * desde el modelo Reparacion con $reparacion->total_abonado.
 *
 * Es el modelo más simple porque solo guarda el monto,
 * una nota opcional, y a qué reparación pertenece.
 */
class Abono extends Model
{
    use HasFactory;

    protected $fillable = [
        'reparacion_id',
        'monto',
        'nota',
    ];

    /**
     * Casts: asegura que el monto siempre tenga 2 decimales.
     * Ejemplo: 50000 se convierte en 50000.00
     */
    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
        ];
    }

    // ── Relaciones ──

    /**
     * La reparación a la que pertenece este abono.
     * Ejemplo: $abono->reparacion->marca → "Samsung"
     *
     * Relación inversa: desde el abono accedes a su reparación.
     */
    public function reparacion(): BelongsTo
    {
        return $this->belongsTo(Reparacion::class);
    }
}
