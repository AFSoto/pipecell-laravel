<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo de cliente del local.
 *
 * Extiende de Model (no de Authenticatable como User)
 * porque los clientes no se loguean en el sistema.
 */
class Cliente extends Model
{
    use HasFactory;

    /**
     * Campos que se pueden llenar masivamente con create() o update().
     * Solo estos 4 campos se pueden asignar.
     */
    protected $fillable = [
        'nombre',
        'telefono',
        'cedula',
        'email',
    ];

    // ── Relaciones ──

    /**
     * Un cliente puede tener muchas reparaciones.
     * Ejemplo: $cliente->reparaciones → todas las veces que ha venido
     */
    public function reparaciones(): HasMany
    {
        return $this->hasMany(Reparacion::class);
    }

    // ── Helpers ──

    /**
     * Cuenta cuántas reparaciones tiene este cliente.
     * Ejemplo: $cliente->totalReparaciones() → 3
     */
    public function totalReparaciones(): int
    {
        return $this->reparaciones()->count();
    }
}
