<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Modelo de venta (cabecera / encabezado).
 *
 * Patrón maestro-detalle: Venta agrupa los VentaItem que la componen.
 * El total se guarda en la BD (no se recalcula) para mantener
 * integridad histórica aunque los precios de los productos cambien.
 */
class Venta extends Model
{
    use HasFactory;

    /**
     * Nombre explícito de la tabla.
     * Laravel buscaría "ventas" correctamente, pero lo declaramos
     * para mayor claridad y consistencia con el resto del proyecto.
     */
    protected $table = 'ventas';

    protected $fillable = [
        'user_id',
        'total',
        'nota',
    ];

    /**
     * Casts: el total siempre se devuelve con 2 decimales.
     */
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
        ];
    }

    // ── Relaciones ──

    /**
     * Los ítems (líneas de detalle) de esta venta.
     * Ejemplo: $venta->items → colección de VentaItem
     */
    public function items(): HasMany
    {
        return $this->hasMany(VentaItem::class);
    }

    /**
     * El usuario que registró la venta.
     * Se nombra 'vendedor' para reflejar el rol semántico en este contexto.
     * Ejemplo: $venta->vendedor → instancia de User
     */
    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Accessors ──

    /**
     * Suma total de unidades vendidas en esta venta (todos los ítems).
     * Útil para mostrar "X productos vendidos" en el resumen.
     * Ejemplo: $venta->cantidad_productos → 4
     */
    protected function cantidadProductos(): Attribute
    {
        return Attribute::get(fn () => $this->items()->sum('cantidad'));
    }

    // ── Scopes ──

    /**
     * Filtra ventas registradas hoy.
     * Uso: Venta::deHoy()->get()
     */
    public function scopeDeHoy($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Filtra ventas del mes y año actuales.
     * Uso: Venta::delMes()->sum('total') → total vendido en el mes
     */
    public function scopeDelMes($query)
    {
        return $query
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }
}
