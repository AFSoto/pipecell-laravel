<?php

namespace App\Enums;

/**
 * Enum para los estados de una caja física.
 *
 * Una caja solo puede estar libre (sin celular) u ocupada (con celular).
 * Cuando se crea una reparación, la caja se marca como ocupada.
 * Cuando se entrega el celular, la caja vuelve a libre.
 */
enum EstadoCaja: string
{
    case Libre = 'libre';
    case Ocupada = 'ocupada';

    /**
     * Texto legible para las vistas.
     * Ejemplo: EstadoCaja::Libre->label() → "Libre"
     */
    public function label(): string
    {
        return match ($this) {
            self::Libre => 'Libre',
            self::Ocupada => 'Ocupada',
        };
    }

    /**
     * Color de Bootstrap para los badges.
     * success = verde (libre, disponible)
     * danger  = rojo (ocupada, tiene un celular)
     */
    public function color(): string
    {
        return match ($this) {
            self::Libre => 'success',
            self::Ocupada => 'danger',
        };
    }
}
