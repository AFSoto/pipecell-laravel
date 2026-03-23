<?php

namespace App\Enums;

/**
 * Enum para los roles de usuario del sistema.
 *
 * Reemplaza la tabla "tipos_usuario" que tenías antes.
 * Solo hay dos roles: admin (maneja todo) y tecnico (opera reparaciones).
 * Si algún día necesitas más roles, solo agregas otro case aquí.
 */
enum RolUsuario: string
{
    case Admin = 'admin';
    case Tecnico = 'tecnico';

    /**
     * Texto legible para mostrar en las vistas.
     * Ejemplo: RolUsuario::Admin->label() → "Administrador"
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Tecnico => 'Técnico',
        };
    }
}
