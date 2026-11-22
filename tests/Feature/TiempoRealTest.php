<?php

namespace Tests\Feature;

use App\Events\CajasCambio;
use App\Events\ReparacionCambio;
use App\Models\Notificacion;
use App\Models\Reparacion;
use App\Models\User;
use App\Support\TiempoReal;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * Tiempo real: qué avisos salen a las otras pantallas, qué devuelve la ruta que
 * las pone al día y cómo se evita pisar los cambios de otra persona.
 *
 * Pusher nunca se llama de verdad: los avisos se capturan con Event::fake().
 */
class TiempoRealTest extends TestCase
{
    use RefreshDatabase;
    use CreaDatosDeReparacion;

    private function fakeAvisos(): void
    {
        Event::fake([ReparacionCambio::class, CajasCambio::class]);
    }

    private function seAviso(string $accion, ?int $id = null, ?string $estado = null): void
    {
        Event::assertDispatched(ReparacionCambio::class, fn (ReparacionCambio $aviso) => $aviso->accion === $accion
            && ($id === null || $aviso->reparacionId === $id)
            && ($estado === null || $aviso->estado === $estado));
    }

    private function datosEdicion(Reparacion $reparacion, array $cambios = []): array
    {
        return array_merge([
            'nombre_cliente'   => $reparacion->nombre_cliente,
            'telefono_cliente' => $reparacion->telefono_cliente,
            'tiene_whatsapp'   => true,
            'marca'            => $reparacion->marca,
            'modelo'           => $reparacion->modelo,
            'valor_total'      => (int) $reparacion->valor_total,
            'costo_repuestos'  => null,
        ], $cambios);
    }

    /** data-edit de la fila tal como lo tiene la pantalla al abrir el modal. */
    private function original(Reparacion $reparacion): array
    {
        return $reparacion->fresh()->load('repuestos.producto', 'abonos')->datosEdicion();
    }

    /** Aviso por WhatsApp ya enviado, esperando que Meta informe la entrega. */
    private function notificacionEnviada(Reparacion $reparacion): Notificacion
    {
        return Notificacion::create([
            'reparacion_id' => $reparacion->id,
            'canal'         => Notificacion::CANAL_WHATSAPP,
            'tipo'          => Notificacion::TIPO_REPARACION_LISTA,
            'telefono'      => '310 123 4567',
            'estado'        => 'enviado',
            'mensaje_id'    => 'wamid.PRUEBA',
        ]);
    }

    /** Lo que manda el webhook de Meta (firmado) cuando cambia la entrega de ese mensaje. */
    private function webhookDeEntrega(string $estado): TestResponse
    {
        config(['services.whatsapp.app_secret' => 'secreto']);

        $cuerpo = json_encode(['entry' => [['changes' => [['field' => 'messages', 'value' => [
            'statuses' => [['id' => 'wamid.PRUEBA', 'status' => $estado, 'timestamp' => '1763740800']],
        ]]]]]]);

        return $this->call('POST', route('webhooks.whatsapp.recibir'), [], [], [], [
            'CONTENT_TYPE'             => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=' . hash_hmac('sha256', $cuerpo, 'secreto'),
        ], $cuerpo);
    }

    // ── Avisos ──

    public function test_crear_una_reparacion_avisa_a_las_otras_pantallas(): void
    {
        $this->fakeAvisos();
        $usuario = $this->crearUsuario();

        $this->actingAs($usuario)->post(route('admin.reparaciones.store'), [
            'nombre_cliente' => 'Juan Pérez',
            'caja_id'        => $this->crearCaja()->id,
            'marca'          => 'Samsung',
            'valor_total'    => '150000',
            'usa_display'    => '0',
        ])->assertRedirect(route('admin.reparaciones.index'));

        $this->seAviso(ReparacionCambio::CREADA, Reparacion::sole()->id);
    }

    public function test_editar_cambiar_de_estado_y_abonar_avisan_cada_uno(): void
    {
        $this->fakeAvisos();
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario); // valor 150.000, abono 50.000
        $this->actingAs($usuario);

        $this->putJson(route('admin.reparaciones.update', $reparacion), $this->datosEdicion($reparacion, ['marca' => 'Xiaomi']))
            ->assertOk();
        $this->seAviso(ReparacionCambio::ACTUALIZADA, $reparacion->id);

        $this->postJson(route('admin.reparaciones.registrarAbono', $reparacion), ['monto' => 20000])
            ->assertOk();
        $this->seAviso(ReparacionCambio::ABONO, $reparacion->id);

        // Al pasar a "Arreglado" salen el cambio de estado y el intento de WhatsApp
        // (aquí "omitido", porque WhatsApp no está configurado en las pruebas)
        $this->patchJson(route('admin.reparaciones.actualizarEstado', $reparacion), ['estado' => 'arreglado'])
            ->assertOk();
        $this->seAviso(ReparacionCambio::ESTADO, $reparacion->id, 'arreglado');
        $this->seAviso(ReparacionCambio::WHATSAPP, $reparacion->id);
    }

    public function test_crear_una_caja_avisa_para_actualizar_las_cajas_libres(): void
    {
        $this->fakeAvisos();

        $this->actingAs($this->crearUsuario())
            ->post(route('admin.cajas.store'), ['grupo' => 'B', 'numero' => 7])
            ->assertRedirect();

        Event::assertDispatched(CajasCambio::class);
    }

    public function test_el_webhook_de_meta_avisa_cuando_cambia_la_entrega(): void
    {
        $this->fakeAvisos();
        $reparacion = $this->crearReparacion($this->crearUsuario());
        $this->notificacionEnviada($reparacion);
        Event::fake([ReparacionCambio::class]); // olvidar los avisos de crear

        $this->webhookDeEntrega('delivered')->assertOk();

        // El webhook no tiene usuario: el aviso va sin autor y le llega a todas las pantallas
        Event::assertDispatched(ReparacionCambio::class, fn (ReparacionCambio $aviso) => $aviso->accion === ReparacionCambio::WHATSAPP
            && $aviso->reparacionId === $reparacion->id
            && $aviso->autor === null);

        // El mismo estado repetido no cambia nada: no vuelve a avisar
        Event::fake([ReparacionCambio::class]);
        $this->webhookDeEntrega('delivered')->assertOk();
        Event::assertNotDispatched(ReparacionCambio::class);
    }

    public function test_el_aviso_de_meta_sale_sin_autor_aunque_haya_alguien_con_sesion(): void
    {
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);
        $this->notificacionEnviada($reparacion);
        Event::fake([ReparacionCambio::class]);

        // Lo informa Meta, no la persona que tiene la sesión abierta
        $this->actingAs($usuario);
        $this->webhookDeEntrega('delivered')->assertOk();

        Event::assertDispatched(ReparacionCambio::class, fn (ReparacionCambio $aviso) => $aviso->accion === ReparacionCambio::WHATSAPP
            && $aviso->autor === null);

        // Un aviso normal, en cambio, sí lleva el primer nombre de quien tiene la sesión
        $this->assertSame('Admin', (new ReparacionCambio($reparacion->id, ReparacionCambio::ABONO))->autor);
    }

    public function test_el_aviso_no_lleva_datos_del_cliente(): void
    {
        $this->fakeAvisos();
        $usuario    = $this->crearUsuario(); // "Admin Pruebas"
        $reparacion = $this->crearReparacion($usuario);

        $this->actingAs($usuario)
            ->patchJson(route('admin.reparaciones.actualizarEstado', $reparacion), ['estado' => 'arreglado'])
            ->assertOk();

        Event::assertDispatched(ReparacionCambio::class, function (ReparacionCambio $aviso) use ($reparacion) {
            return $aviso->accion === ReparacionCambio::ESTADO
                && $aviso->broadcastWith() === [
                    'id'     => $reparacion->id,
                    'accion' => 'estado',
                    'estado' => 'arreglado',
                    'autor'  => 'Admin', // solo el primer nombre de quien lo hizo
                ]
                && $aviso->broadcastOn()[0]->name === 'private-taller';
        });
    }

    public function test_no_avisa_si_el_cambio_no_se_guardo(): void
    {
        $this->fakeAvisos();
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario); // saldo 100.000
        Event::fake([ReparacionCambio::class]);

        $this->actingAs($usuario)
            ->postJson(route('admin.reparaciones.registrarAbono', $reparacion), ['monto' => 500000])
            ->assertStatus(422);

        Event::assertNotDispatched(ReparacionCambio::class);
    }

    public function test_si_la_transaccion_se_revierte_no_sale_ningun_aviso(): void
    {
        $this->fakeAvisos();

        try {
            DB::transaction(function () {
                TiempoReal::avisar(new ReparacionCambio(1, ReparacionCambio::ACTUALIZADA, autor: 'Ana'));
                throw new \RuntimeException('Falló al guardar');
            });
        } catch (\RuntimeException) {
            // La transacción se revirtió
        }

        Event::assertNotDispatched(ReparacionCambio::class);

        // Con la transacción confirmada, el aviso sí sale
        DB::transaction(fn () => TiempoReal::avisar(new ReparacionCambio(1, ReparacionCambio::ACTUALIZADA, autor: 'Ana')));

        Event::assertDispatched(ReparacionCambio::class);
    }

    public function test_si_pusher_falla_el_guardado_sigue_igual(): void
    {
        Exceptions::fake();
        Broadcast::extend('falla', fn () => new class extends Broadcaster {
            public function auth($request) {}

            public function validAuthenticationResponse($request, $result) {}

            public function broadcast(array $channels, $event, array $payload = [])
            {
                throw new BroadcastException('Pusher no responde');
            }
        });
        config(['broadcasting.connections.falla' => ['driver' => 'falla'], 'broadcasting.default' => 'falla']);

        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);

        $this->actingAs($usuario)
            ->putJson(route('admin.reparaciones.update', $reparacion), $this->datosEdicion($reparacion, ['marca' => 'Motorola']))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('Motorola', $reparacion->fresh()->marca);
        Exceptions::assertReported(BroadcastException::class);
    }

    // ── Canal privado ──

    public function test_el_canal_del_taller_solo_admite_usuarios_activos(): void
    {
        $permiso = Broadcast::getChannels()->get('taller');

        $activo   = $this->crearUsuario();
        $inactivo = User::create([
            'nombre' => 'Ex Empleado', 'email' => 'ex@pruebas.test', 'password' => 'password',
            'role'   => 'tecnico', 'estado' => 'inactivo',
        ]);

        $this->assertTrue($permiso($activo));
        $this->assertFalse($permiso($inactivo));
    }

    // ── Puesta al día ──

    public function test_sincronizar_devuelve_las_filas_los_contadores_y_las_cajas_libres(): void
    {
        $usuario = $this->crearUsuario();
        $primera = $this->crearReparacion($usuario);
        $segunda = $this->crearReparacion($usuario, ['nombre_cliente' => "D'Angelo"]);
        $libre   = $this->crearCaja('B', 1);
        $segunda->update(['estado' => 'arreglado']);

        $respuesta = $this->actingAs($usuario)
            ->getJson(route('admin.reparaciones.sincronizar', ['ids' => "{$primera->id},abc,{$segunda->id},99999"]))
            ->assertOk()
            ->assertJsonPath('filas.99999', null)
            ->assertJsonMissingPath('filas.abc')
            ->assertJsonPath('contadores', ['en_proceso' => 1, 'arreglado' => 1, 'entregado' => 0])
            ->assertJsonPath('cajas_libres', [['id' => $libre->id, 'nombre' => $libre->nombre_display]]);

        $filaSegunda = $respuesta->json("filas.{$segunda->id}");
        $this->assertStringContainsString("data-id=\"{$segunda->id}\"", $filaSegunda);
        $this->assertStringContainsString('data-estado="arreglado"', $filaSegunda);
        $this->assertStringContainsString('D&#039;Angelo', $filaSegunda);
    }

    public function test_la_fila_que_llega_en_vivo_es_igual_a_la_de_la_lista(): void
    {
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);
        $this->actingAs($usuario);

        $lista = $this->get(route('admin.reparaciones.index'))->assertOk()->getContent();
        $fila  = $this->getJson(route('admin.reparaciones.sincronizar', ['ids' => $reparacion->id]))
            ->json("filas.{$reparacion->id}");

        $this->assertStringContainsString(trim($fila), $lista);
    }

    public function test_sincronizar_pide_sesion(): void
    {
        $this->get(route('admin.reparaciones.sincronizar'))->assertRedirect(route('login'));
    }

    // ── Cambios de otra persona con el modal abierto ──

    public function test_editar_con_los_datos_al_dia_se_guarda(): void
    {
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);

        $this->actingAs($usuario)->putJson(route('admin.reparaciones.update', $reparacion),
            $this->datosEdicion($reparacion, ['marca' => 'Xiaomi', 'original' => $this->original($reparacion)])
        )->assertOk();

        $this->assertSame('Xiaomi', $reparacion->fresh()->marca);
    }

    public function test_no_pisa_lo_que_otra_persona_cambio_mientras_el_modal_estaba_abierto(): void
    {
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);
        $this->actingAs($usuario);

        // La tablet abre el modal...
        $alAbrir = $this->original($reparacion);

        // ...y mientras tanto el mostrador corrige el teléfono
        $this->putJson(route('admin.reparaciones.update', $reparacion),
            $this->datosEdicion($reparacion, ['telefono_cliente' => '320 999 8888'])
        )->assertOk();

        // La tablet guarda con los datos viejos: se rechaza y no se pisa el teléfono
        $this->putJson(route('admin.reparaciones.update', $reparacion),
            $this->datosEdicion($reparacion, ['marca' => 'Xiaomi', 'original' => $alAbrir])
        )->assertStatus(409)
            ->assertJsonPath('conflicto', true)
            ->assertJsonPath('message', 'Otra persona cambió esta reparación mientras la editabas. Carga sus cambios y vuelve a guardar.');

        $reparacion->refresh();
        $this->assertSame('320 999 8888', $reparacion->telefono_cliente);
        $this->assertSame('Samsung', $reparacion->marca);
    }

    public function test_un_abono_o_un_cambio_de_estado_no_cuentan_como_conflicto(): void
    {
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);
        $this->actingAs($usuario);

        $alAbrir = $this->original($reparacion);

        // Mientras el modal está abierto, otra persona cobra y cambia el estado
        $this->postJson(route('admin.reparaciones.registrarAbono', $reparacion), ['monto' => 10000])->assertOk();
        $this->patchJson(route('admin.reparaciones.actualizarEstado', $reparacion), ['estado' => 'arreglado'])->assertOk();

        // El modal no edita esos campos: se guarda normal
        $this->putJson(route('admin.reparaciones.update', $reparacion),
            $this->datosEdicion($reparacion, ['marca' => 'Xiaomi', 'original' => $alAbrir])
        )->assertOk();

        $this->assertSame('Xiaomi', $reparacion->fresh()->marca);
    }

    public function test_datos_originales_con_listas_adentro_dan_error_de_validacion_y_no_500(): void
    {
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario);

        // Solo pasa con una petición armada a mano: la pantalla manda valores simples
        $this->actingAs($usuario)->putJson(route('admin.reparaciones.update', $reparacion),
            $this->datosEdicion($reparacion, ['original' => ['nombre_cliente' => ['x']]])
        )->assertStatus(422)
            ->assertJsonValidationErrors('original.nombre_cliente');

        $this->assertSame('Juan Pérez', $reparacion->fresh()->nombre_cliente);
    }

    // ── Respuestas que la pantalla escribe tal cual ──

    public function test_el_abono_responde_el_abonado_y_el_saldo_ya_calculados(): void
    {
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario); // valor 150.000, abono 50.000

        // La pantalla escribe estos totales tal cual: si le sumara el abono a lo que muestra
        // la fila, lo contaría dos veces cuando el tiempo real ya la había puesto al día
        $this->actingAs($usuario)
            ->postJson(route('admin.reparaciones.registrarAbono', $reparacion), ['monto' => 30000])
            ->assertOk()
            ->assertJsonPath('abonado', 80000)
            ->assertJsonPath('saldo', 70000);
    }
}
