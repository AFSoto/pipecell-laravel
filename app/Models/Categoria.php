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

    /**
     * Las categorías de pantallas (displays) se reconocen porque su nombre contiene
     * esta palabra, sin importar mayúsculas (ej: "Repuestos - Pantallas"). Es la única
     * definición: la usan productos (campo marco, marca y referencia) y reparaciones
     * (selector "¿Se usó display?").
     */
    public const PALABRA_PANTALLA = 'pantalla';

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

    /**
     * Filtra las categorías de pantallas (ver PALABRA_PANTALLA).
     * LIKE no distingue mayúsculas con la intercalación de MySQL ni en SQLite.
     * Uso: Categoria::dePantallas()->get()
     */
    public function scopeDePantallas($query)
    {
        return $query->where('nombre', 'like', '%' . self::PALABRA_PANTALLA . '%');
    }

    // ── Helpers ──

    /**
     * Indica si es una categoría de pantallas (ver PALABRA_PANTALLA).
     */
    public function esDePantallas(): bool
    {
        return str_contains(mb_strtolower($this->nombre), self::PALABRA_PANTALLA);
    }

    /**
     * Verifica si la categoría está activa.
     * Ejemplo: if ($categoria->estaActiva()) { ... }
     */
    public function estaActiva(): bool
    {
        return $this->estado === 'activo';
    }
}
