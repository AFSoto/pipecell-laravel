<?php

namespace App\Enums;

/**
 * Resultado de un intento de aviso al cliente (WhatsApp, y más adelante SMS).
 *
 * - Enviado: el proveedor aceptó el mensaje. Si llegó o se leyó lo confirma
 *            después el webhook (Notificacion::entregado_at / leido_at).
 * - Fallido: el proveedor respondió con error, o el webhook avisó que no se
 *            pudo entregar (se guarda el motivo).
 * - Omitido: no se intentó enviar (sin teléfono, sin WhatsApp, no configurado...).
 *
 * Los textos que ve el usuario están en Notificacion::resumen() y paraVista().
 */
enum EstadoNotificacion: string
{
    case Enviado = 'enviado';
    case Fallido = 'fallido';
    case Omitido = 'omitido';
}
