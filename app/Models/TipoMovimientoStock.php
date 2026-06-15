<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoMovimientoStock extends Model
{
    protected $table = 'tipos_movimiento_stock';

    protected $fillable = ['nombre'];

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoStock::class);
    }
}
