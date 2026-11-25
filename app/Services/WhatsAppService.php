<?php

namespace App\Services;

use App\Exceptions\WhatsAppException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Cliente mínimo de la API oficial de WhatsApp (Meta Cloud API).
 *
 * Solo envía mensajes de plantilla: los avisos que inicia el negocio
 * (como "tu equipo está listo") exigen una plantilla aprobada por Meta.
 *
 * Configuración en config/services.php → 'whatsapp' (variables WHATSAPP_* del .env).
 */
class WhatsAppService
{
    /**
     * Errores frecuentes de Meta traducidos para mostrarlos en la plataforma.
     * Los que no estén aquí se muestran con el mensaje original de Meta.
     */
    private const ERRORES_CONOCIDOS = [
        190    => 'El token de WhatsApp venció o no es válido. Revisa WHATSAPP_TOKEN.',
        131026 => 'El número no tiene WhatsApp o no puede recibir mensajes.',
        131049 => 'Meta no lo entregó para no saturar al cliente con mensajes. Intenta más tarde.',
        131030 => 'El número no está en la lista de destinatarios de prueba de Meta.',
        131056 => 'Se enviaron demasiados mensajes seguidos a este número. Intenta más tarde.',
        132000 => 'La cantidad de datos no coincide con la plantilla aprobada en Meta.',
        132001 => 'La plantilla no existe o todavía no está aprobada en Meta.',
        133010 => 'El número de WhatsApp de la plataforma no está registrado en Meta.',
    ];

    /**
     * Hay token y número configurados (si no, no se intenta enviar nada).
     */
    public function estaConfigurado(): bool
    {
        return filled(config('services.whatsapp.token'))
            && filled(config('services.whatsapp.phone_number_id'));
    }

    /**
     * Envía una plantilla aprobada con parámetros de texto en el cuerpo.
     *
     * @param  string    $telefono   Número en formato internacional sin "+" (ej: 573101234567)
     * @param  string[]  $parametros Valores de {{1}}, {{2}}, ... en orden
     * @return string    ID del mensaje en Meta (wamid)
     *
     * @throws WhatsAppException si Meta rechaza el envío o no hay conexión
     */
    public function enviarPlantilla(string $telefono, string $plantilla, string $idioma, array $parametros): string
    {
        $url = sprintf(
            '%s/%s/%s/messages',
            rtrim(config('services.whatsapp.api_url') ?: 'https://graph.facebook.com', '/'),
            config('services.whatsapp.api_version'),
            config('services.whatsapp.phone_number_id')
        );

        try {
            $respuesta = Http::withToken(config('services.whatsapp.token'))
                ->acceptJson()
                ->timeout(10)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to'                => $telefono,
                    'type'              => 'template',
                    'template'          => [
                        'name'       => $plantilla,
                        'language'   => ['code' => $idioma],
                        'components' => [[
                            'type'       => 'body',
                            'parameters' => array_map(
                                fn ($valor) => ['type' => 'text', 'text' => $this->limpiarParametro($valor)],
                                array_values($parametros)
                            ),
                        ]],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new WhatsAppException('No se pudo conectar con WhatsApp. Revisa la conexión del servidor.', previous: $e);
        }

        if ($respuesta->failed()) {
            throw new WhatsAppException($this->mensajeDeError($respuesta));
        }

        $mensajeId = $respuesta->json('messages.0.id');

        if (! $mensajeId) {
            throw new WhatsAppException('WhatsApp respondió sin el ID del mensaje.');
        }

        return $mensajeId;
    }

    /**
     * Meta rechaza parámetros con saltos de línea, tabulaciones o más de 4 espacios seguidos.
     */
    private function limpiarParametro(mixed $valor): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $valor));
    }

    /**
     * Texto en español para un código de error de Meta (null si no es uno conocido).
     * Lo usan el envío y el webhook de estados de entrega.
     */
    public static function mensajePorCodigo(mixed $codigo): ?string
    {
        return is_numeric($codigo) ? (self::ERRORES_CONOCIDOS[(int) $codigo] ?? null) : null;
    }

    private function mensajeDeError(Response $respuesta): string
    {
        return self::mensajePorCodigo($respuesta->json('error.code'))
            ?? $respuesta->json('error.message')
            ?? "WhatsApp respondió con error HTTP {$respuesta->status()}.";
    }
}
