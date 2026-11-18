<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Cloud API (Meta)
    |--------------------------------------------------------------------------
    |
    | Aviso automático al cliente cuando su reparación pasa a "Arreglado".
    | Si falta el token o el phone_number_id, no se envía nada y el intento
    | queda registrado como "omitido".
    |
    | - token:           token permanente de un System User (whatsapp_business_messaging)
    | - phone_number_id: ID del número en WhatsApp Manager (no es el número de teléfono)
    | - plantilla_lista: plantilla "Utility" aprobada con 3 variables:
    |                    {{1}} cliente, {{2}} equipo, {{3}} saldo pendiente
    |
    */

    'whatsapp' => [
        'token'           => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'api_version'     => env('WHATSAPP_API_VERSION', 'v26.0'),
        'plantilla_lista' => env('WHATSAPP_PLANTILLA_LISTA', 'reparacion_lista'),
        'idioma'          => env('WHATSAPP_IDIOMA', 'es'),
    ],

];
