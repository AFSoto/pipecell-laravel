<?php

namespace App\Enums;

/**
 * Resultado de un intento de aviso al cliente (WhatsApp, y más adelante SMS).
 *
 * - Enviado: el proveedor aceptó el mensaje.
 * - Fallido: el proveedor respondió con error (se guarda el motivo).
 * - Omitido: no se intentó enviar (sin teléfono, sin WhatsApp, no configurado...).
 */
enum EstadoNotificacion: string
{
    case Enviado = 'enviado';
    case Fallido = 'fallido';
    case Omitido = 'omitido';

    /**
     * Texto legible para las vistas.
     */
    public function label(): string
    {
        return match ($this) {
            self::Enviado => 'Enviado',
            self::Fallido => 'Falló',
            self::Omitido => 'No enviado',
        };
    }

    /**
     * Color del badge, con la misma convención que EstadoReparacion.
     */
    public function color(): string
    {
        return match ($this) {
            self::Enviado => 'success',
            self::Fallido => 'danger',
            self::Omitido => 'secondary',
        };
    }
}
