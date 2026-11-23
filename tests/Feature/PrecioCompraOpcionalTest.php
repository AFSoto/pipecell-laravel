<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Js;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * El precio de compra es opcional: vacío se guarda como NULL ("sin precio de compra"),
 * que no es lo mismo que 0 (costó $0). Un producto sin precio tiene que poder crearse,
 * editarse y verse en la lista y en las tarjetas sin romper el botón Editar.
 */
class PrecioCompraOpcionalTest extends TestCase
{
    use RefreshDatabase;
    use CreaDatosDeReparacion;

    private Categoria $categoria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->crearUsuario());
        $this->categoria = Categoria::create(['nombre' => 'Accesorios', 'estado' => 'activo']);
    }

    /** Lo que mandan los formularios de crear y editar; el precio de compra va vacío. */
    private function datosFormulario(array $cambios = []): array
    {
        return array_merge([
            'categoria_id'  => $this->categoria->id,
            'codigo'        => '',
            'nombre'        => 'Cargador 20W USB-C',
            'descripcion'   => '',
            'precio_compra' => '',
            'precio_venta'  => '45000',
            'stock'         => '3',
            'stock_minimo'  => '1',
        ], $cambios);
    }

    private function crearProducto(?int $precioCompra, string $nombre = 'Cargador 20W USB-C'): Producto
    {
        return Producto::create([
            'categoria_id'  => $this->categoria->id,
            'nombre'        => $nombre,
            'precio_compra' => $precioCompra,
            'precio_venta'  => 45000,
            'stock'         => 3,
            'stock_minimo'  => 1,
            'estado'        => 'activo',
        ]);
    }

    public function test_se_puede_crear_un_producto_sin_precio_de_compra(): void
    {
        $this->post(route('admin.productos.store'), $this->datosFormulario())
            ->assertRedirect(route('admin.productos.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success')
            ->assertSessionMissing('error');

        $this->assertNull(Producto::sole()->precio_compra);
    }

    public function test_con_precio_de_compra_se_guarda_como_antes_y_cero_no_es_vacio(): void
    {
        $this->post(route('admin.productos.store'), $this->datosFormulario(['precio_compra' => '20000']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->post(route('admin.productos.store'), $this->datosFormulario(['nombre' => 'Cable regalado', 'precio_compra' => '0']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame('20000.00', Producto::where('nombre', 'Cargador 20W USB-C')->sole()->precio_compra);
        $this->assertSame('0.00', Producto::where('nombre', 'Cable regalado')->sole()->precio_compra);
    }

    public function test_un_precio_negativo_o_con_letras_sigue_rechazado_sin_romper_la_pagina(): void
    {
        foreach (['-1', 'abc'] as $precio) {
            $this->from(route('admin.productos.index'))
                ->post(route('admin.productos.store'), $this->datosFormulario(['precio_compra' => $precio]))
                ->assertRedirect(route('admin.productos.index'))
                ->assertSessionHasErrors('precio_compra');

            // La página vuelve con el modal de crear y lo escrito (antes, "abc" daba error 500)
            $this->get(route('admin.productos.index'))->assertOk()->assertSee('Corrige los siguientes errores');
        }

        $this->assertSame(0, Producto::count());
    }

    public function test_al_editar_se_puede_quitar_el_precio_de_compra(): void
    {
        $producto = $this->crearProducto(20000);

        $this->patch(route('admin.productos.update', $producto), $this->datosFormulario())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertNull($producto->fresh()->precio_compra);
    }

    public function test_editar_un_producto_sin_precio_de_compra_lo_deja_sin_precio(): void
    {
        $producto = $this->crearProducto(null);

        // El modal de editar deja vacío el precio de compra y lo manda así
        $this->patch(route('admin.productos.update', $producto), $this->datosFormulario(['precio_venta' => '47000']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $producto->refresh();
        $this->assertNull($producto->precio_compra);
        $this->assertSame('47000.00', $producto->precio_venta);
    }

    public function test_lista_y_tarjetas_muestran_bien_un_producto_sin_precio_de_compra(): void
    {
        $sinPrecio = $this->crearProducto(null, 'Cargador sin precio');
        $conPrecio = $this->crearProducto(20000, 'Cargador con precio');

        // Las dos vistas usan los mismos partials: se revisan las dos
        foreach (['tarjetas', 'lista'] as $vista) {
            $html = $this->get(route('admin.productos.index', ['vista' => $vista]))->assertOk()->getContent();

            // Editar recibe null como precio de compra (con {{ }} quedaba ", ," y el botón no funcionaba)
            $this->assertMatchesRegularExpression($this->argumentosDeEditar($sinPrecio, 'null'), $html, "Vista {$vista}");
            $this->assertMatchesRegularExpression($this->argumentosDeEditar($conPrecio, Js::from('20000.00')->toHtml()), $html, "Vista {$vista}");

            // El precio de compra tachado solo aparece en el que tiene precio, y nunca como "$0"
            preg_match_all('#line-through">\s*\$([\d.]+)#', $html, $tachados);
            $this->assertSame(['20.000'], $tachados[1], "Vista {$vista}");
        }
    }

    public function test_la_ganancia_unitaria_no_se_inventa_sin_precio_de_compra(): void
    {
        $this->assertNull($this->crearProducto(null)->ganancia_unitaria);
        $this->assertEquals(25000, $this->crearProducto(20000, 'Otro cargador')->ganancia_unitaria);
    }

    /**
     * Patrón de la llamada a abrirEditarProducto() de ese producto, desde su nombre hasta el
     * precio de venta: nombre, descripción (vacía), precio de compra y precio de venta.
     */
    private function argumentosDeEditar(Producto $producto, string $precioCompraJs): string
    {
        return '#' . preg_quote(Js::from($producto->nombre)->toHtml(), '#') . ',\s*'
            . preg_quote(Js::from('')->toHtml(), '#') . ',\s*'
            . preg_quote($precioCompraJs, '#') . ',\s*45000\.00,#';
    }
}
