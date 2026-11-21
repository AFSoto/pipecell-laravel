<?php

use App\Http\Controllers\WhatsappWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhooks de servicios externos
|--------------------------------------------------------------------------
|
| Se registran en bootstrap/app.php sin el grupo "web": no abren sesión, no
| ponen cookies y no piden token CSRF (quien llama es un servidor, no el
| navegador). Cada controlador valida que la llamada sea legítima.
|
*/

// WhatsApp (Meta): verificación de la URL y estados de entrega de los avisos
Route::get('/webhooks/whatsapp', [WhatsappWebhookController::class, 'verificar'])
    ->name('webhooks.whatsapp.verificar');

Route::post('/webhooks/whatsapp', [WhatsappWebhookController::class, 'recibir'])
    ->name('webhooks.whatsapp.recibir');
