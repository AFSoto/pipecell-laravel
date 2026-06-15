<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modelo de producto del inventario.
 *
 * Centraliza toda la lógica relacionada con artículos:
 * precios, stock, categoría e imágenes.
 * Los accessors calculan ganancia y alertas de stock sin guardar datos extra en la BD.
 */
class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'categoria_id',
        'tipo_producto_id',
        'codigo',
        'nombre',
        'descripcion',
        'marco',
        'marca',
        'referencia',
        'precio_compra',
        'precio_venta',
        'stock',
        'stock_minimo',
        'estado',
    ];

    /**
     * Casts: los precios siempre se devuelven con exactamente 2 decimales.
     * Esto evita problemas de comparación de flotantes al calcular ganancias.
     */
    protected function casts(): array
    {
        return [
            'precio_compra' => 'decimal:2',
            'precio_venta'  => 'decimal:2',
            'marco'         => 'boolean',
        ];
    }

    // ── Relaciones ──

    /**
     * La categoría a la que pertenece este producto.
     * Ejemplo: $producto->categoria → instancia de Categoria
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function tipoProducto(): BelongsTo
    {
        return $this->belongsTo(TipoProducto::class);
    }

    /**
     * Todas las imágenes de este producto (galería completa).
     * Ejemplo: $producto->imagenes → colección de ProductoImagen
     */
    public function imagenes(): HasMany
    {
        return $this->hasMany(ProductoImagen::class);
    }

    /**
     * Solo la imagen principal del producto.
     * Se usa en tarjetas y listados donde solo se muestra una foto.
     * Ejemplo: $producto->imagenPrincipal → instancia de ProductoImagen o null
     */
    public function imagenPrincipal(): HasOne
    {
        return $this->hasOne(ProductoImagen::class)->where('es_principal', true);
    }

    /**
     * Los ítems de venta donde aparece este producto.
     * Útil para calcular el historial de ventas o las unidades vendidas.
     * Ejemplo: $producto->ventaItems → colección de VentaItem
     */
    public function ventaItems(): HasMany
    {
        return $this->hasMany(VentaItem::class);
    }

    // ── Accessors (campos calculados) ──

    /**
     * Ganancia unitaria: precio de venta menos precio de compra.
     * No se guarda en BD — se calcula al vuelo.
     * Ejemplo: $producto->ganancia_unitaria → 15000.00
     */
    protected function gananciaUnitaria(): Attribute
    {
        return Attribute::get(fn () => $this->precio_venta - $this->precio_compra);
    }

    /**
     * Indica si el stock actual está en el umbral de alerta.
     * Retorna true cuando stock <= stock_minimo para disparar advertencias.
     * Ejemplo: $producto->stock_bajo → true
     */
    protected function stockBajo(): Attribute
    {
        return Attribute::get(fn () => $this->stock <= $this->stock_minimo);
    }

    // ── Scopes ──

    /**
     * Filtra solo productos activos.
     * Uso: Producto::activos()->get()
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Filtra productos cuyo stock llegó o bajó del mínimo.
     * Uso: Producto::stockBajo()->get() → lista de reposición pendiente
     */
    public function scopeStockBajo($query)
    {
        // whereColumn compara dos columnas de la misma tabla
        return $query->whereColumn('stock', '<=', 'stock_minimo');
    }

    /**
     * Filtra productos de una categoría específica.
     * Uso: Producto::deCategoria(3)->activos()->get()
     *
     * @param int $categoriaId ID de la categoría a filtrar
     */
    public function scopeDeCategoria($query, int $categoriaId)
    {
        return $query->where('categoria_id', $categoriaId);
    }

    // ── Helpers ──

    /**
     * Verifica si hay suficiente stock para atender una cantidad solicitada.
     *
     * @param int $cantidad Unidades requeridas (default 1)
     * Ejemplo: if ($producto->tieneStock(5)) { ... }
     */
    public function tieneStock(int $cantidad = 1): bool
    {
        return $this->stock >= $cantidad;
    }

    /**
     * Descuenta unidades del stock después de una venta.
     * Lanza una excepción si no hay stock suficiente para
     * evitar stock negativo en la BD.
     *
     * @param int $cantidad Unidades a descontar
     * @throws \RuntimeException Si el stock es insuficiente
     */
    public function descontarStock(int $cantidad): void
    {
        if (! $this->tieneStock($cantidad)) {
            throw new \RuntimeException(
                "Stock insuficiente para el producto '{$this->nombre}'. "
                . "Disponible: {$this->stock}, solicitado: {$cantidad}."
            );
        }

        // decrement actualiza en BD de forma atómica (previene race conditions)
        $this->decrement('stock', $cantidad);
    }
}
