<?php

namespace App\Enums;

/**
 * Enum para los estados de una reparación.
 *
 * Reemplaza el ENUM de MySQL — la validación se hace desde PHP.
 * Laravel castea automáticamente el string de la BD a este Enum
 * cuando lo configuramos en el modelo con casts().
 */
enum EstadoReparacion: string
{
    // Cada "case" es un estado posible.
    // Lo que está después del "=" es el valor que se guarda en la BD.
    case EnProceso = 'en_proceso';
    case Arreglado = 'arreglado';
    case Entregado = 'entregado';

    /**
     * Devuelve el texto legible para mostrar en las vistas.
     * Ejemplo: EstadoReparacion::EnProceso->label() → "En proceso"
     *
     * match() es como un switch pero más limpio y seguro.
     */
    public function label(): string
    {
        return match ($this) {
            self::EnProceso => 'En proceso',
            self::Arreglado => 'Arreglado',
            self::Entregado => 'Entregado',
        };
    }

    /**
     * Devuelve la clase de color de Bootstrap para los badges.
     * Ejemplo en Blade: <span class="badge bg-{{ $reparacion->estado->color() }}">
     *
     * warning = amarillo (en proceso)
     * info    = azul (arreglado)
     * success = verde (entregado)
     */
    public function color(): string
    {
        return match ($this) {
            self::EnProceso => 'warning',
            self::Arreglado => 'info',
            self::Entregado => 'success',
        };
    }
}
