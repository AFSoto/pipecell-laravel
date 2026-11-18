<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Error al enviar un mensaje por la API de WhatsApp (Meta Cloud API).
 *
 * El mensaje ya viene listo para mostrarse al usuario, en español cuando
 * el código de error de Meta es conocido.
 */
class WhatsAppException extends RuntimeException
{
}
