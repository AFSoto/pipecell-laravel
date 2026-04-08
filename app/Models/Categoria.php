<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo de categoría de productos.
 *
 * Agrupa los productos en familias lógicas para facilitar la navegación
 * del inventario. Ejemplos: "Accesorios de Carga", "Repuestos - Pantallas".
 */
class Categoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
    ];

    // ── Relaciones ──

    /**
     * Todos los productos que pertenecen a esta categoría.
     * Ejemplo: $categoria->productos → colección de Producto
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    // ── Scopes ──

    /**
     * Filtra solo las categorías activas.
     * Uso: Categoria::activas()->get()
     */
    public function scopeActivas($query)
    {
        return $query->where('estado', 'activo');
    }

    // ── Helpers ──

    /**
     * Verifica si la categoría está activa.
     * Ejemplo: if ($categoria->estaActiva()) { ... }
     */
    public function estaActiva(): bool
    {
        return $this->estado === 'activo';
    }
}
