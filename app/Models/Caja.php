<?php

namespace App\Models;

use App\Enums\EstadoCaja;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Modelo de caja física del local.
 *
 * Cada caja tiene un grupo (A, B, C) y un número (1, 2, 3).
 * El nombre de display "A1", "B3" se genera automáticamente
 * con un Accessor (no se guarda en la BD).
 */
class Caja extends Model
{
    use HasFactory;

    protected $fillable = [
        'grupo',
        'numero',
        'descripcion',
        'estado',
    ];

    /**
     * Casts: convierte el string 'libre' al Enum EstadoCaja::Libre.
     * Así puedes hacer $caja->estado->label() → "Libre"
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoCaja::class,
        ];
    }

    // ── Accessors ──

    /**
     * Genera el nombre de display combinando grupo + número.
     *
     * Esto NO se guarda en la BD — se calcula al vuelo.
     * Ejemplo: $caja->nombre_display → "A1"
     */
    protected function nombreDisplay(): Attribute
    {
        return Attribute::get(fn () => $this->grupo . $this->numero);
    }

    // ── Scopes ──

    /**
     * Scope: filtra solo cajas libres.
     * Uso: Caja::libres()->get() → solo las disponibles
     */
    public function scopeLibres($query)
    {
        return $query->where('estado', EstadoCaja::Libre);
    }

    /**
     * Scope: filtra solo cajas ocupadas.
     * Uso: Caja::ocupadas()->get()
     */
    public function scopeOcupadas($query)
    {
        return $query->where('estado', EstadoCaja::Ocupada);
    }

    /**
     * Scope: filtra cajas por grupo.
     * Uso: Caja::delGrupo('A')->get() → todas las del grupo A
     */
    public function scopeDelGrupo($query, string $grupo)
    {
        return $query->where('grupo', strtoupper($grupo));
    }

    // ── Helpers ──

    /**
     * Verifica si la caja está libre.
     * Ejemplo: if ($caja->estaLibre()) { ... }
     */
    public function estaLibre(): bool
    {
        return $this->estado === EstadoCaja::Libre;
    }

    /**
     * Marca la caja como ocupada.
     * Se llama cuando se crea una reparación.
     */
    public function ocupar(): void
    {
        $this->update(['estado' => EstadoCaja::Ocupada]);
    }

    /**
     * Marca la caja como libre.
     * Se llama cuando se entrega el celular.
     */
    public function liberar(): void
    {
        $this->update(['estado' => EstadoCaja::Libre]);
    }
}
