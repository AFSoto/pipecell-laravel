<?php

namespace Tests\Feature;

use App\Models\MovimientoStock;
use App\Models\Notificacion;
use App\Models\Reparacion;
use App\Services\ReparacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * Display usado en la reparación: descuento y devolución de stock.
 */
class ReparacionDisplayTest extends TestCase
{
    use RefreshDatabase;
    use CreaDatosDeReparacion;

    private function datosFormulario(array $cambios = []): array
    {
        return array_merge([
            'nombre_cliente'    => 'Juan Pérez',
            'telefono_cliente'  => '310 123 4567',
            'tiene_whatsapp'    => '1',
            'caja_id'           => $this->crearCaja()->id,
            'marca'             => 'Samsung',
            'modelo'            => 'A10',
            'descripcion_falla' => 'Pantalla rota',
            'valor_total'       => '150000',
            'costo_repuestos'   => '',
            'abono_inicial'     => '',
            'usa_display'       => '0',
            'display_cantidad'  => '1',
        ], $cambios);
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

    public function test_crear_con_display_descuenta_stock_registra_movimiento_y_calcula_costo(): void
    {
        $usuario  = $this->crearUsuario();
        $pantalla = $this->crearPantalla(stock: 5, precioCompra: 40000);

        $respuesta = $this->actingAs($usuario)->post(route('admin.reparaciones.store'), $this->datosFormulario([
            'usa_display'      => '1',
            'display_id'       => $pantalla->id,
            'display_cantidad' => '2',
        ]));

        $respuesta->assertRedirect(route('admin.reparaciones.index'));
        $respuesta->assertSessionHas('success', fn ($mensaje) => str_contains($mensaje, '2 unidades'));

        $reparacion = Reparacion::sole();

        $this->assertSame(3, $pantalla->fresh()->stock);
        $this->assertEquals(80000, $reparacion->costo_repuestos); // 40.000 × 2
        $this->assertDatabaseHas('reparacion_repuestos', [
            'reparacion_id' => $reparacion->id,
            'producto_id'   => $pantalla->id,
            'cantidad'      => 2,
        ]);

        $movimiento = MovimientoStock::with('tipoMovimiento')->sole();
        $this->assertSame(-2, $movimiento->cantidad);
        $this->assertSame(ReparacionService::MOTIVO_USO, $movimiento->tipoMovimiento->nombre);
        $this->assertStringContainsString("Reparación #{$reparacion->id}", $movimiento->nota);
    }

    public function test_respeta_el_costo_de_repuestos_escrito_por_el_usuario(): void
    {
        $usuario  = $this->crearUsuario();
        $pantalla = $this->crearPantalla();

        $this->actingAs($usuario)->post(route('admin.reparaciones.store'), $this->datosFormulario([
            'usa_display'     => '1',
            'display_id'      => $pantalla->id,
            'costo_repuestos' => '55000',
        ]));

        $this->assertEquals(55000, Reparacion::sole()->costo_repuestos);
    }

    public function test_cantidad_mayor_al_stock_no_crea_la_reparacion(): void
    {
        $usuario  = $this->crearUsuario();
        $pantalla = $this->crearPantalla(stock: 1);

        $respuesta = $this->actingAs($usuario)->post(route('admin.reparaciones.store'), $this->datosFormulario([
            'usa_display'      => '1',
            'display_id'       => $pantalla->id,
            'display_cantidad' => '2',
        ]));

        $respuesta->assertSessionHasErrors('display_id');
        $this->assertDatabaseCount('reparaciones', 0);
        $this->assertSame(1, $pantalla->fresh()->stock);
    }

    public function test_marcar_display_sin_elegir_cual_da_error(): void
    {
        $usuario = $this->crearUsuario();

        $respuesta = $this->actingAs($usuario)->post(route('admin.reparaciones.store'), $this->datosFormulario([
            'usa_display' => '1',
            'display_id'  => '',
        ]));

        $respuesta->assertSessionHasErrors('display_id');
        $this->assertDatabaseCount('reparaciones', 0);
    }

    public function test_sin_marcar_display_se_ignora_el_select_y_no_descuenta(): void
    {
        $usuario  = $this->crearUsuario();
        $pantalla = $this->crearPantalla(stock: 5);

        $this->actingAs($usuario)->post(route('admin.reparaciones.store'), $this->datosFormulario([
            'usa_display' => '0',
            'display_id'  => $pantalla->id,
        ]))->assertRedirect(route('admin.reparaciones.index'));

        $this->assertSame(5, $pantalla->fresh()->stock);
        $this->assertDatabaseCount('reparacion_repuestos', 0);
    }

    public function test_no_acepta_productos_que_no_son_pantallas(): void
    {
        $usuario  = $this->crearUsuario();
        $bateria  = $this->crearPantalla('Batería A10');
        $bateria->categoria->update(['nombre' => 'Repuestos - Baterías']);

        $respuesta = $this->actingAs($usuario)->post(route('admin.reparaciones.store'), $this->datosFormulario([
            'usa_display' => '1',
            'display_id'  => $bateria->id,
        ]));

        $respuesta->assertSessionHasErrors('display_id');
    }

    public function test_editar_cambiando_de_display_devuelve_el_anterior_y_descuenta_el_nuevo(): void
    {
        $usuario    = $this->crearUsuario();
        $pantallaA  = $this->crearPantalla('Pantalla A10', stock: 5);
        $pantallaB  = $this->crearPantalla('Pantalla A20', stock: 5, precioCompra: 60000);
        $reparacion = $this->crearReparacion($usuario, ['display_id' => $pantallaA->id, 'display_cantidad' => 1]);

        $respuesta = $this->actingAs($usuario)->putJson(
            route('admin.reparaciones.update', $reparacion),
            $this->datosEdicion($reparacion, ['usa_display' => true, 'display_id' => $pantallaB->id, 'display_cantidad' => 1])
        );

        $respuesta->assertOk()
            ->assertJsonPath('data.display_id', $pantallaB->id)
            ->assertJsonPath('data.display_nombre', 'Pantalla A20')
            ->assertJsonPath("stocks.{$pantallaA->id}", 5)
            ->assertJsonPath("stocks.{$pantallaB->id}", 4);

        $this->assertSame(5, $pantallaA->fresh()->stock);
        $this->assertSame(4, $pantallaB->fresh()->stock);
        $this->assertEquals(60000, $reparacion->fresh()->costo_repuestos);

        $devolucion = MovimientoStock::with('tipoMovimiento')->where('producto_id', $pantallaA->id)->where('cantidad', '>', 0)->sole();
        $this->assertSame(ReparacionService::MOTIVO_DEVOLUCION, $devolucion->tipoMovimiento->nombre);
    }

    public function test_editar_la_cantidad_mueve_solo_la_diferencia(): void
    {
        $usuario    = $this->crearUsuario();
        $pantalla   = $this->crearPantalla(stock: 5);
        $reparacion = $this->crearReparacion($usuario, ['display_id' => $pantalla->id, 'display_cantidad' => 1]);
        $this->assertSame(4, $pantalla->fresh()->stock);

        $this->actingAs($usuario)->putJson(route('admin.reparaciones.update', $reparacion),
            $this->datosEdicion($reparacion, ['usa_display' => true, 'display_id' => $pantalla->id, 'display_cantidad' => 3])
        )->assertOk();
        $this->assertSame(2, $pantalla->fresh()->stock);

        $this->actingAs($usuario)->putJson(route('admin.reparaciones.update', $reparacion),
            $this->datosEdicion($reparacion, ['usa_display' => true, 'display_id' => $pantalla->id, 'display_cantidad' => 2])
        )->assertOk();
        $this->assertSame(3, $pantalla->fresh()->stock);
        $this->assertDatabaseHas('reparacion_repuestos', ['reparacion_id' => $reparacion->id, 'cantidad' => 2]);
    }

    public function test_editar_quitando_el_display_lo_devuelve_al_inventario(): void
    {
        $usuario    = $this->crearUsuario();
        $pantalla   = $this->crearPantalla(stock: 5);
        $reparacion = $this->crearReparacion($usuario, ['display_id' => $pantalla->id, 'display_cantidad' => 2]);

        $this->actingAs($usuario)->putJson(route('admin.reparaciones.update', $reparacion),
            $this->datosEdicion($reparacion, ['usa_display' => false, 'display_id' => $pantalla->id])
        )->assertOk()->assertJsonPath('data.display_id', null);

        $this->assertSame(5, $pantalla->fresh()->stock);
        $this->assertDatabaseCount('reparacion_repuestos', 0);
    }

    public function test_editar_sin_campos_de_display_no_toca_el_inventario(): void
    {
        $usuario    = $this->crearUsuario();
        $pantalla   = $this->crearPantalla(stock: 5);
        $reparacion = $this->crearReparacion($usuario, ['display_id' => $pantalla->id, 'display_cantidad' => 1]);

        // Simula una página vieja abierta que no envía usa_display / display_id
        $this->actingAs($usuario)->putJson(route('admin.reparaciones.update', $reparacion),
            $this->datosEdicion($reparacion, ['marca' => 'Samsung Galaxy'])
        )->assertOk();

        $this->assertSame(4, $pantalla->fresh()->stock);
        $this->assertDatabaseCount('reparacion_repuestos', 1);
        $this->assertSame('Samsung Galaxy', $reparacion->fresh()->marca);
    }

    public function test_editar_permite_mantener_el_display_aunque_su_stock_quedo_en_cero(): void
    {
        $usuario    = $this->crearUsuario();
        $pantalla   = $this->crearPantalla(stock: 1);
        $reparacion = $this->crearReparacion($usuario, ['display_id' => $pantalla->id, 'display_cantidad' => 1]);
        $this->assertSame(0, $pantalla->fresh()->stock);

        $this->actingAs($usuario)->putJson(route('admin.reparaciones.update', $reparacion),
            $this->datosEdicion($reparacion, [
                'telefono_cliente' => '320 000 0000',
                'usa_display'      => true,
                'display_id'       => $pantalla->id,
                'display_cantidad' => 1,
            ])
        )->assertOk();

        $this->assertSame(0, $pantalla->fresh()->stock);
        $this->assertSame('320 000 0000', $reparacion->fresh()->telefono_cliente);
    }

    public function test_el_listado_muestra_el_display_y_el_indicador_de_whatsapp(): void
    {
        $usuario    = $this->crearUsuario();
        $pantalla   = $this->crearPantalla(stock: 3);
        $reparacion = $this->crearReparacion($usuario, ['display_id' => $pantalla->id, 'display_cantidad' => 1]);

        Notificacion::create([
            'reparacion_id' => $reparacion->id,
            'canal'         => Notificacion::CANAL_WHATSAPP,
            'tipo'          => Notificacion::TIPO_REPARACION_LISTA,
            'telefono'      => $reparacion->telefono_cliente,
            'estado'        => 'enviado',
            'mensaje_id'    => 'wamid.PRUEBA',
        ]);

        $this->actingAs($usuario)
            ->get(route('admin.reparaciones.index', ['estado' => 'todos']))
            ->assertOk()
            ->assertSee('Display: Pantalla Samsung A10 ×1')
            ->assertSee('Pantalla Samsung A10 — stock 2')
            ->assertSee('data-whatsapp="{&quot;estado&quot;:&quot;enviado&quot;', false);
    }
}
