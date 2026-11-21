<?php

/*
|--------------------------------------------------------------------------
| Tiempo real (broadcasting)
|--------------------------------------------------------------------------
|
| La plataforma usa Pusher Channels (plan Sandbox, gratuito) para avisar al
| instante a las otras pantallas del taller. El hosting es compartido: no hay
| servidor de WebSockets propio ni worker de colas, por eso los avisos se
| envían en la misma petición (ShouldBroadcastNow, ver App\Support\TiempoReal).
|
| Sin BROADCAST_CONNECTION=pusher no se envía nada y todo funciona como antes.
| Las demás conexiones (log, null, reverb, ably) vienen de la configuración
| por defecto de Laravel.
|
*/

return [

    'default' => env('BROADCAST_CONNECTION', 'null'),

    'connections' => [

        'pusher' => [
            'driver'  => 'pusher',
            'key'     => env('PUSHER_APP_KEY'),
            'secret'  => env('PUSHER_APP_SECRET'),
            'app_id'  => env('PUSHER_APP_ID'),
            'options' => [
                'cluster'   => env('PUSHER_APP_CLUSTER'),
                'host'      => env('PUSHER_HOST') ?: 'api-' . env('PUSHER_APP_CLUSTER', 'mt1') . '.pusher.com',
                'port'      => env('PUSHER_PORT', 443),
                'scheme'    => env('PUSHER_SCHEME', 'https'),
                'encrypted' => true,
                'useTLS'    => env('PUSHER_SCHEME', 'https') === 'https',
            ],
            // Si Pusher no responde, el guardado no puede quedarse esperando:
            // máximo 3 segundos y el aviso se pierde (queda en laravel.log)
            'client_options' => [
                'connect_timeout' => 3,
                'timeout'         => 3,
            ],
        ],

    ],

];
