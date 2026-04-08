<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de imagen de producto.
 *
 * Cada producto puede tener varias imágenes en una galería.
 * Solo el binario vive en storage/; aquí se guarda la ruta relativa.
 * Una de las imágenes puede ser marcada como principal (es_principal = true)
 * para mostrarse en listados y tarjetas de producto.
 */
class ProductoImagen extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'path',
        'es_principal',
    ];

    /**
     * Casts: convierte el tinyint de la BD a boolean de PHP.
     * Así $imagen->es_principal devuelve true/false en lugar de 0/1.
     */
    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
        ];
    }

    // ── Relaciones ──

    /**
     * El producto al que pertenece esta imagen.
     * Ejemplo: $imagen->producto → instancia de Producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
