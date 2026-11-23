<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Repuesto del inventario usado en una reparación (hoy: el display).
 *
 * costo_unitario guarda el precio de compra del momento, solo como historial:
 * no se suma al costo de Repuestos de la reparación. Queda en null si el producto
 * no tenía precio de compra (no se sabe el costo; no es un costo de $0).
 *
 * El stock no se toca desde aquí: lo maneja ReparacionService::sincronizarDisplay(),
 * que usa ProductoService::ajustarStock() para dejar el movimiento registrado.
 */
class ReparacionRepuesto extends Model
{
    protected $table = 'reparacion_repuestos';

    protected $fillable = [
        'reparacion_id',
        'producto_id',
        'cantidad',
        'costo_unitario',
    ];

    protected function casts(): array
    {
        return [
            'producto_id'    => 'integer',
            'cantidad'       => 'integer',
            'costo_unitario' => 'decimal:2',
        ];
    }

    // ── Relaciones ──

    public function reparacion(): BelongsTo
    {
        return $this->belongsTo(Reparacion::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
