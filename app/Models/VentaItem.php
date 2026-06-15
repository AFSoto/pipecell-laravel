<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de ítem de venta (línea de detalle).
 *
 * Representa un producto dentro de una venta específica.
 * Se guarda el precio al momento de la venta (precio_unitario) porque
 * el precio del producto puede cambiar posteriormente y el historial
 * debe reflejar lo que realmente se cobró.
 */
class VentaItem extends Model
{
    use HasFactory;

    /**
     * Nombre real de la tabla.
     * Laravel inferiría "venta_items" correctamente, pero lo declaramos
     * explícitamente para evitar ambigüedades.
     */
    protected $table = 'venta_items';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    /**
     * Casts: asegura que los valores monetarios siempre sean decimales con 2 cifras.
     */
    protected function casts(): array
    {
        return [
            'precio_unitario' => 'decimal:2',
            'subtotal'        => 'decimal:2',
        ];
    }

    // ── Relaciones ──

    /**
     * La venta a la que pertenece este ítem.
     * Ejemplo: $item->venta → instancia de Venta
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /**
     * El producto vendido en esta línea.
     * Ejemplo: $item->producto → instancia de Producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
