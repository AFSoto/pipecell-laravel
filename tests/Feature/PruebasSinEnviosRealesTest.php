<?php

namespace Tests\Feature;

use App\Services\WhatsAppService;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Las pruebas nunca le escriben a nadie, aunque el .env de quien las corre tenga la clave de
 * WhatsApp de verdad (Cosend): varias pasan reparaciones a "Arreglado", y eso avisa al cliente.
 * Dos barreras: phpunit.xml deja WhatsApp sin configurar, y TestCase no deja salir ningún
 * pedido HTTP que no esté simulado con Http::fake().
 */
class PruebasSinEnviosRealesTest extends TestCase
{
    public function test_whatsapp_no_esta_configurado_aunque_el_env_tenga_la_clave(): void
    {
        $this->assertSame('', config('services.whatsapp.token'));
        $this->assertSame('', config('services.whatsapp.phone_number_id'));
        $this->assertSame('https://graph.facebook.com', config('services.whatsapp.api_url'));
        $this->assertSame('es', config('services.whatsapp.idioma'));
        $this->assertSame('', config('services.whatsapp.app_secret'));
        $this->assertFalse(app(WhatsAppService::class)->estaConfigurado());
    }

    public function test_un_pedido_sin_simular_falla_sin_salir_a_internet(): void
    {
        $this->expectException(StrayRequestException::class);

        // Una dirección local que no responde: si faltara la barrera, la prueba fallaría con
        // un error de conexión, sin escribirle a nadie
        Http::post('http://127.0.0.1:9/v26.0/1234567890/messages', ['to' => '573101234567']);
    }
}
