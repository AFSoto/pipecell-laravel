<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoStock extends Model
{
    protected $table = 'movimientos_stock';
    protected $fillable = [
        'producto_id',
        'user_id',
        'tipo_movimiento_id',
        'cantidad',
        'stock_anterior',
        'stock_nuevo',
        'nota',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tipoMovimiento(): BelongsTo
    {
        return $this->belongsTo(TipoMovimientoStock::class, 'tipo_movimiento_id');
    }
}
