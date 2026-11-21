<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Models\Reparacion;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * Aviso por WhatsApp cuando la reparación pasa a "Arreglado".
 * Nunca sale a Meta de verdad: Http::fake() + preventStrayRequests().
 */
class NotificacionWhatsappTest extends TestCase
{
    use RefreshDatabase;
    use CreaDatosDeReparacion;

    private const URL_META = 'https://graph.facebook.com/v26.0/1234567890/messages';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.token'           => 'token-de-prueba',
            'services.whatsapp.phone_number_id' => '1234567890',
            'services.whatsapp.api_version'     => 'v26.0',
            'services.whatsapp.plantilla_lista' => 'reparacion_lista',
            'services.whatsapp.idioma'          => 'es',
        ]);

        Http::preventStrayRequests();
    }

    private function fakeEnvioExitoso(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.PRUEBA']]])]);
    }

    private function cambiarEstado(Reparacion $reparacion, string $estado)
    {
        return $this->patchJson(route('admin.reparaciones.actualizarEstado', $reparacion), ['estado' => $estado]);
    }

    public function test_pasar_a_arreglado_envia_la_plantilla_al_cliente(): void
    {
        $this->fakeEnvioExitoso();
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario); // valor 150.000, abono 50.000

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado')
            ->assertOk()
            ->assertJsonPath('notificacion.estado', 'enviado')
            ->assertJsonPath('notificacion.mensaje', 'WhatsApp enviado a 310 123 4567')
            // Estado actual del indicador: enviado, todavía sin confirmación de entrega
            ->assertJsonPath('whatsapp.estado', 'enviado')
            ->assertJsonPath('whatsapp.entrega', null);

        Http::assertSent(function (Request $request) {
            return $request->url() === self::URL_META
                && $request->hasHeader('Authorization', 'Bearer token-de-prueba')
                && $request['to'] === '573101234567'
                && $request['type'] === 'template'
                && $request['template']['name'] === 'reparacion_lista'
                && $request['template']['language']['code'] === 'es'
                && $request['template']['components'][0]['parameters'] === [
                    ['type' => 'text', 'text' => 'Juan Pérez'],
                    ['type' => 'text', 'text' => 'Samsung A10'],
                    ['type' => 'text', 'text' => '$100.000'],
                ];
        });

        $this->assertDatabaseHas('notificaciones', [
            'reparacion_id' => $reparacion->id,
            'canal'         => 'whatsapp',
            'estado'        => 'enviado',
            'mensaje_id'    => 'wamid.PRUEBA',
            'user_id'       => $usuario->id,
        ]);
    }

    public function test_sin_telefono_no_envia_y_queda_omitido(): void
    {
        Http::fake();
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario, ['telefono_cliente' => null]);

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado')
            ->assertOk()
            ->assertJsonPath('notificacion.estado', 'omitido')
            ->assertJsonPath('notificacion.mensaje', 'No se envió WhatsApp: el cliente no tiene teléfono registrado.');

        Http::assertNothingSent();
    }

    public function test_cliente_sin_whatsapp_no_envia(): void
    {
        Http::fake();
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario, ['tiene_whatsapp' => false]);

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado')
            ->assertJsonPath('notificacion.estado', 'omitido')
            ->assertJsonPath('notificacion.mensaje', 'No se envió WhatsApp: el cliente no tiene WhatsApp.');

        Http::assertNothingSent();
    }

    public function test_telefono_fijo_o_invalido_no_envia(): void
    {
        Http::fake();
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario, ['telefono_cliente' => '601 234 5678']);

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado')
            ->assertJsonPath('notificacion.estado', 'omitido');

        Http::assertNothingSent();
    }

    public function test_sin_configuracion_no_envia_y_lo_informa(): void
    {
        Http::fake();
        config(['services.whatsapp.token' => null]);
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado')
            ->assertJsonPath('notificacion.estado', 'omitido')
            ->assertJsonPath('notificacion.mensaje', 'No se envió WhatsApp: WhatsApp no está configurado en la plataforma.');

        Http::assertNothingSent();
    }

    public function test_error_de_meta_queda_como_fallido_y_el_estado_igual_cambia(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'error' => ['message' => '(#131030) Recipient phone number not in allowed list', 'code' => 131030],
        ], 400)]);
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('notificacion.estado', 'fallido')
            ->assertJsonPath('notificacion.mensaje', 'No se pudo enviar el WhatsApp: El número no está en la lista de destinatarios de prueba de Meta.');

        $this->assertSame('arreglado', $reparacion->fresh()->estado->value);
    }

    public function test_no_reenvia_si_ya_se_habia_avisado(): void
    {
        $this->fakeEnvioExitoso();
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado')->assertJsonPath('notificacion.estado', 'enviado');
        $this->cambiarEstado($reparacion, 'en_proceso')->assertJsonPath('notificacion', null);
        $this->cambiarEstado($reparacion, 'arreglado')->assertJsonPath('notificacion', null);

        Http::assertSentCount(1);
    }

    public function test_de_entregado_a_arreglado_no_envia(): void
    {
        Http::fake();
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario, ['abono_inicial' => 150000]);
        $reparacion->update(['estado' => 'entregado']);

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado')
            ->assertOk()
            ->assertJsonPath('notificacion', null);

        Http::assertNothingSent();
    }

    public function test_el_boton_reintenta_despues_de_un_fallo(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'Service temporarily unavailable', 'code' => 2]], 503)
            ->push(['messages' => [['id' => 'wamid.REINTENTO']]]),
        ]);
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado')->assertJsonPath('notificacion.estado', 'fallido');

        $this->postJson(route('admin.reparaciones.enviarWhatsapp', $reparacion))
            ->assertOk()
            ->assertJsonPath('notificacion.estado', 'enviado');

        Http::assertSentCount(2);
        $this->assertSame(2, Notificacion::count());
        $this->assertSame('wamid.REINTENTO', $reparacion->ultimoWhatsapp()->first()->mensaje_id);
    }

    public function test_el_boton_no_reenvia_si_el_aviso_ya_salio(): void
    {
        $this->fakeEnvioExitoso();
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado')->assertJsonPath('notificacion.estado', 'enviado');

        // Otra persona con la página abierta desde antes todavía ve "Reintentar" y lo pulsa
        $this->postJson(route('admin.reparaciones.enviarWhatsapp', $reparacion))
            ->assertOk()
            ->assertJsonPath('notificacion', null)
            ->assertJsonPath('message', 'Este aviso ya se había enviado: no se mandó otra vez.')
            ->assertJsonPath('whatsapp.estado', 'enviado');

        Http::assertSentCount(1);
        $this->assertSame(1, Notificacion::count());
    }

    public function test_un_error_interno_al_enviar_queda_guardado_como_fallido(): void
    {
        $this->mock(WhatsAppService::class, function (MockInterface $mock) {
            $mock->shouldReceive('estaConfigurado')->andReturnTrue();
            $mock->shouldReceive('enviarPlantilla')->andThrow(new \RuntimeException('Fallo inesperado'));
        });
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado')
            ->assertOk()
            ->assertJsonPath('notificacion.estado', 'fallido')
            // Queda en la base: al recargar, la fila sigue mostrando el fallo y "Reintentar"
            ->assertJsonPath('whatsapp.estado', 'fallido');

        $this->assertDatabaseHas('notificaciones', [
            'reparacion_id' => $reparacion->id,
            'estado'        => 'fallido',
            'detalle'       => Notificacion::DETALLE_ERROR_INTERNO,
        ]);
    }

    public function test_el_boton_solo_funciona_en_arreglado(): void
    {
        Http::fake();
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);

        $this->actingAs($usuario)
            ->postJson(route('admin.reparaciones.enviarWhatsapp', $reparacion))
            ->assertStatus(422);

        Http::assertNothingSent();
    }
}
