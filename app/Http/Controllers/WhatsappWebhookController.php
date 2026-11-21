<?php

namespace App\Http\Controllers;

use App\Services\NotificacionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Webhook de la API oficial de WhatsApp (Meta): avisa si un mensaje se entregó,
 * se leyó o no se pudo entregar.
 *
 * Es público porque Meta no tiene sesión (rutas en routes/webhooks.php, sin el
 * grupo "web": ni sesión, ni cookies, ni CSRF). Se protege así:
 *   - GET  (Meta verifica la URL): solo responde si el token coincide con WHATSAPP_VERIFY_TOKEN.
 *   - POST (estados): solo se acepta con la firma X-Hub-Signature-256 calculada con WHATSAPP_APP_SECRET.
 */
class WhatsappWebhookController extends Controller
{
    public function __construct(
        private NotificacionService $notificacionService
    ) {}

    /**
     * Meta llama con hub.mode=subscribe, hub.verify_token y hub.challenge cuando se
     * guarda la URL del webhook. PHP convierte los puntos de los parámetros en "_".
     */
    public function verificar(Request $request): Response
    {
        $token = (string) config('services.whatsapp.verify_token');

        if ($token !== ''
            && $request->query('hub_mode') === 'subscribe'
            && hash_equals($token, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'), 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('Token de verificación inválido.', 403);
    }

    /**
     * Estados de los mensajes: entry[].changes[] con field = "messages" y value.statuses[].
     * Siempre responde 200 a un aviso con firma válida, aunque no corresponda a ningún
     * mensaje de la plataforma: si no, Meta lo reintenta durante días.
     */
    public function recibir(Request $request): Response
    {
        if (! $this->firmaValida($request)) {
            return response('Firma inválida.', 403);
        }

        foreach ((array) $request->input('entry', []) as $entrada) {
            foreach ((array) data_get($entrada, 'changes', []) as $cambio) {
                if (data_get($cambio, 'field') !== 'messages') {
                    continue;
                }

                foreach ((array) data_get($cambio, 'value.statuses', []) as $estado) {
                    if (is_array($estado)) {
                        $this->notificacionService->registrarEstadoDeEntrega($estado);
                    }
                }
            }
        }

        return response('OK', 200);
    }

    /**
     * Meta firma el cuerpo exacto con el App Secret (HMAC SHA-256). Sin secreto
     * configurado no se acepta nada: cualquiera podría inventar estados.
     */
    private function firmaValida(Request $request): bool
    {
        $secreto = (string) config('services.whatsapp.app_secret');

        if ($secreto === '') {
            return false;
        }

        $esperada = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secreto);

        return hash_equals($esperada, (string) $request->header('X-Hub-Signature-256'));
    }
}
