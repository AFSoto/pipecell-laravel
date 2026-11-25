<?php

namespace Tests\Feature;

use App\Enums\EstadoNotificacion;
use App\Models\Notificacion;
use App\Models\Reparacion;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /** $extra: lo demás que manda la pantalla, como avisar_whatsapp (lo que se eligió en el modal). */
    private function cambiarEstado(Reparacion $reparacion, string $estado, array $extra = [])
    {
        return $this->patchJson(route('admin.reparaciones.actualizarEstado', $reparacion), ['estado' => $estado] + $extra);
    }

    public function test_pasar_a_arreglado_envia_la_plantilla_al_cliente(): void
    {
        $this->fakeEnvioExitoso();
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario); // Juan Pérez, Samsung A10

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
                // {{1}} nombre y {{2}} equipo: el mensaje no dice el saldo (decisión del dueño)
                && $request['template']['components'][0]['parameters'] === [
                    ['type' => 'text', 'text' => 'Juan Pérez'],
                    ['type' => 'text', 'text' => 'Samsung A10'],
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

    // ── Al pasar a «Arreglado» se puede elegir no avisar (el cliente está en el taller) ──

    public function test_elegir_no_avisar_pasa_a_arreglado_sin_llamar_a_meta(): void
    {
        Http::fake();
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado', ['avisar_whatsapp' => false])
            ->assertOk()
            ->assertJsonPath('success', true)
            // Sin aviso flotante: lo eligió quien lo marcó. La fila muestra «no enviado» y por qué
            ->assertJsonPath('notificacion', null)
            ->assertJsonPath('whatsapp.estado', 'omitido')
            ->assertJsonPath('whatsapp.mensaje', 'No se envió WhatsApp: se eligió no avisar al pasar a Arreglado.');

        Http::assertNothingSent();
        $this->assertSame('arreglado', $reparacion->fresh()->estado->value);
        $this->assertDatabaseHas('notificaciones', [
            'reparacion_id' => $reparacion->id,
            'estado'        => 'omitido',
            'detalle'       => Notificacion::DETALLE_NO_AVISAR,
            'user_id'       => $usuario->id,
        ]);
    }

    /** [lo que manda la pantalla]. Sin el campo es una pantalla con el JS de antes: avisa como siempre. */
    public static function pedidosQueAvisan(): array
    {
        return [
            'con avisar_whatsapp: true' => [['avisar_whatsapp' => true]],
            'sin avisar_whatsapp'       => [[]],
        ];
    }

    #[DataProvider('pedidosQueAvisan')]
    public function test_con_la_opcion_marcada_o_sin_el_campo_avisa(array $extra): void
    {
        $this->fakeEnvioExitoso();
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado', $extra)
            ->assertOk()
            ->assertJsonPath('notificacion.estado', 'enviado');

        Http::assertSentCount(1);
    }

    public function test_despues_de_no_avisar_el_boton_de_la_fila_si_envia(): void
    {
        $this->fakeEnvioExitoso();
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado', ['avisar_whatsapp' => false])
            ->assertJsonPath('whatsapp.estado', 'omitido');

        // Al final el cliente no vino: se le avisa desde la fila
        $this->postJson(route('admin.reparaciones.enviarWhatsapp', $reparacion))
            ->assertOk()
            ->assertJsonPath('notificacion.estado', 'enviado');

        Http::assertSentCount(1);
    }

    public function test_no_avisar_cuando_ya_se_habia_avisado_no_guarda_nada(): void
    {
        $this->fakeEnvioExitoso();
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado')->assertJsonPath('notificacion.estado', 'enviado');
        $this->cambiarEstado($reparacion, 'en_proceso');
        $this->cambiarEstado($reparacion, 'arreglado', ['avisar_whatsapp' => false])
            ->assertOk()
            ->assertJsonPath('notificacion', null)
            ->assertJsonPath('whatsapp.estado', 'enviado');

        Http::assertSentCount(1);
        $this->assertSame(1, Notificacion::count());
    }

    public function test_avisar_whatsapp_que_no_es_si_o_no_se_rechaza_y_el_estado_no_cambia(): void
    {
        Http::fake();
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado', ['avisar_whatsapp' => 'quizás'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('avisar_whatsapp');

        $this->assertSame('en_proceso', $reparacion->fresh()->estado->value);
        Http::assertNothingSent();
    }

    // ── Cosend (mismo número del local): la misma API de Meta en otra dirección ──

    public function test_con_cosend_el_aviso_va_a_su_api_con_la_clave_de_conexion(): void
    {
        config([
            'services.whatsapp.api_url' => 'https://api.cosend.app',
            'services.whatsapp.token'   => 'csk_live_clavedeprueba',
        ]);
        Http::fake(['api.cosend.app/*' => Http::response(['messages' => [['id' => 'wamid.COSEND']]])]);
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado')->assertJsonPath('notificacion.estado', 'enviado');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.cosend.app/v26.0/1234567890/messages'
            && $request->hasHeader('Authorization', 'Bearer csk_live_clavedeprueba')
            && $request['template']['name'] === 'reparacion_lista');
        $this->assertSame('wamid.COSEND', $reparacion->ultimoWhatsapp()->first()->mensaje_id);
    }

    /**
     * [HTTP, respuesta de Cosend, lo que dice el aviso]. Los errores propios de Cosend vienen en
     * el formato de Meta, pero con códigos de texto: sin traducir saldrían en inglés.
     */
    public static function erroresDeCosend(): array
    {
        $cupo = 'No se pudo enviar el WhatsApp: Se acabaron los 1.000 mensajes gratis del mes en Cosend. '
            . 'Agrega una tarjeta en Cosend o espera al mes siguiente.';

        return [
            'cupo del mes agotado'    => [402, ['error' => ['message' => 'Monthly allowance reached', 'code' => 'quota_exceeded']], $cupo],
            '402 sin código conocido' => [402, ['error' => ['message' => 'Payment required']], $cupo],
            'número desconectado'     => [400, ['error' => ['message' => 'Connection is not routable', 'code' => 'connection_not_routable', 'reason' => 'disconnected']],
                'No se pudo enviar el WhatsApp: El número no está conectado en Cosend. Abre WhatsApp Business en el celular y revisa la conexión en Cosend.'],
        ];
    }

    #[DataProvider('erroresDeCosend')]
    public function test_los_errores_de_cosend_se_explican_en_espanol(int $http, array $respuesta, string $mensaje): void
    {
        config(['services.whatsapp.api_url' => 'https://api.cosend.app']);
        Http::fake(['api.cosend.app/*' => Http::response($respuesta, $http)]);
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);

        $this->actingAs($usuario);
        $this->cambiarEstado($reparacion, 'arreglado')
            ->assertOk()
            ->assertJsonPath('notificacion.estado', 'fallido')
            ->assertJsonPath('notificacion.mensaje', $mensaje)
            // Queda fallido: la fila ofrece «Reintentar» (con el cupo de vuelta o el número conectado)
            ->assertJsonPath('whatsapp.estado', 'fallido');
    }

    // ── La fila dice si al pasar a «Arreglado» hay que preguntar (data-avisar-a) ──

    /**
     * [datos de la reparación, estado del último aviso (null: ninguno), si WhatsApp está
     * configurado, lo que debe tener data-avisar-a]. Con un celular, la pantalla pregunta si se
     * avisa; vacío, pasa directo: no se avisaría (la misma regla del envío).
     */
    public static function avisarA(): array
    {
        return [
            'celular'                   => [['telefono_cliente' => '310 123 4567'],     null,      true,  '310 123 4567'],
            'celular con +57 y guiones' => [['telefono_cliente' => '+57 310-123-4567'], null,      true,  '310 123 4567'],
            'sin teléfono'              => [['telefono_cliente' => null],               null,      true,  ''],
            'sin WhatsApp'              => [['tiene_whatsapp' => false],                null,      true,  ''],
            'fijo'                      => [['telefono_cliente' => '601 234 5678'],     null,      true,  ''],
            'WhatsApp sin configurar'   => [[],                                         null,      false, ''],
            'ya se le avisó'            => [[],                                         'enviado', true,  ''],
            'se eligió no avisar'       => [[],                                         'omitido', true,  '310 123 4567'],
            'el aviso anterior falló'   => [[],                                         'fallido', true,  '310 123 4567'],
        ];
    }

    #[DataProvider('avisarA')]
    public function test_la_fila_dice_a_que_celular_se_avisaria(array $datos, ?string $ultimoAviso, bool $configurado, string $esperado): void
    {
        if (! $configurado) {
            config(['services.whatsapp.token' => null]);
        }
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario, $datos);

        if ($ultimoAviso) {
            Notificacion::create([
                'reparacion_id' => $reparacion->id,
                'canal'         => Notificacion::CANAL_WHATSAPP,
                'tipo'          => Notificacion::TIPO_REPARACION_LISTA,
                'telefono'      => $reparacion->telefono_cliente,
                'estado'        => EstadoNotificacion::from($ultimoAviso),
            ]);
        }

        $html = $this->actingAs($usuario)->get(route('admin.reparaciones.index'))->assertOk()->getContent();

        $this->assertSame(1, preg_match('#data-id="' . $reparacion->id . '"[^>]*?data-avisar-a="([^"]*)"#', $html, $avisarA),
            'La fila no tiene data-avisar-a');
        $this->assertSame($esperado, html_entity_decode($avisarA[1]));
    }
}
