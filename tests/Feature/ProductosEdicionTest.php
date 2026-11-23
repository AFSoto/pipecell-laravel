<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Js;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * Editar y desactivar productos (desde la lista o las tarjetas):
 * - si editar falla la validación, vuelve el modal de EDITAR con lo escrito; nunca el de
 *   crear, donde "corregir y guardar" crearía un producto nuevo en vez de actualizar;
 * - después de guardar o desactivar se vuelve a la misma lista (filtros, vista y página).
 */
class ProductosEdicionTest extends TestCase
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

    private function crearProducto(string $codigo, string $nombre): Producto
    {
        return Producto::create([
            'categoria_id'  => $this->categoria->id,
            'codigo'        => $codigo,
            'nombre'        => $nombre,
            'precio_compra' => 5000,
            'precio_venta'  => 9000,
            'stock'         => 3,
            'stock_minimo'  => 1,
            'estado'        => 'activo',
        ]);
    }

    /** Lo que manda el formulario de editar. */
    private function datosFormulario(Producto $producto, array $cambios = []): array
    {
        return array_merge([
            'categoria_id'  => $producto->categoria_id,
            'codigo'        => $producto->codigo,
            'nombre'        => $producto->nombre,
            'descripcion'   => '',
            'precio_compra' => 5000,
            'precio_venta'  => 9000,
            'stock'         => 3,
            'stock_minimo'  => 1,
        ], $cambios);
    }

    public function test_editar_con_errores_vuelve_al_modal_de_editar_y_no_al_de_crear(): void
    {
        $this->crearProducto('A-1', 'Producto A');
        $b           = $this->crearProducto('B-1', 'Producto B');
        $lista       = route('admin.productos.index', ['vista' => 'lista']);
        $descripcion = "Con \"comillas\" y\nun salto de línea";

        $this->from($lista)
            ->patch(route('admin.productos.update', $b), $this->datosFormulario($b, [
                'codigo'      => 'A-1',
                'nombre'      => 'Producto B renombrado',
                'descripcion' => $descripcion,
            ]))
            ->assertRedirect($lista)
            ->assertSessionHasErrorsIn('editarProducto', ['codigo']);

        $this->assertTrue(session('errors')->getBag('default')->isEmpty(), 'Los errores de editar cayeron en la bolsa del modal de crear');

        $html = $this->get($lista)->assertOk()->getContent();

        // El modal de editar vuelve con sus errores y con lo que se había escrito, escapado
        $this->assertStringContainsString('id="editar-errores"', $html);
        $this->assertSame(1, substr_count($html, Js::from('Producto B renombrado')->toHtml()));
        $this->assertStringContainsString(Js::from($descripcion)->toHtml(), $html);

        // El de crear no se abre y su formulario sale vacío
        $this->assertDoesNotMatchRegularExpression("#getElementById\('modal-producto'\);\s*modal\.classList\.remove\('hidden'\)#", $html);
        $this->assertStringNotContainsString('id="crear-errores"', $html);
        $this->assertMatchesRegularExpression('#id="crear-nombre"[^>]*value=""#', $html);
    }

    public function test_crear_con_errores_sigue_abriendo_el_modal_de_crear_con_lo_escrito(): void
    {
        $this->crearProducto('A-1', 'Producto A');
        $lista = route('admin.productos.index');

        $this->from($lista)->post(route('admin.productos.store'), [
            'categoria_id'  => $this->categoria->id,
            'codigo'        => 'A-1',
            'nombre'        => 'Producto nuevo',
            'precio_compra' => 5000,
            'precio_venta'  => 9000,
            'stock'         => 3,
            'stock_minimo'  => 1,
        ])
            ->assertRedirect($lista)
            ->assertSessionHasErrors(['codigo']);

        $html = $this->get($lista)->assertOk()->getContent();

        $this->assertMatchesRegularExpression("#getElementById\('modal-producto'\);\s*modal\.classList\.remove\('hidden'\)#", $html);
        $this->assertMatchesRegularExpression('#id="crear-nombre"[^>]*value="Producto nuevo"#', $html);
        $this->assertStringNotContainsString('id="editar-errores"', $html);
    }

    public function test_despues_de_editar_vuelve_a_la_misma_lista_y_baja_al_producto(): void
    {
        $b = $this->crearProducto('B-1', 'Producto B');

        $this->patch(route('admin.productos.update', $b), $this->datosFormulario($b, [
            'precio_venta' => 9500,
            'volver'       => "categoria={$this->categoria->id}&page=2&vista=lista&otra=cosa",
        ]))->assertRedirect(
            route('admin.productos.index', ['categoria' => $this->categoria->id, 'page' => 2, 'vista' => 'lista']) . "#producto-{$b->id}"
        );

        $this->assertSame(9500, (int) $b->fresh()->precio_venta);
    }

    public function test_despues_de_desactivar_vuelve_a_la_misma_lista(): void
    {
        $b = $this->crearProducto('B-1', 'Producto B');

        $this->delete(route('admin.productos.destroy', $b), ['volver' => 'buscar=Producto&vista=lista'])
            ->assertRedirect(route('admin.productos.index', ['buscar' => 'Producto', 'vista' => 'lista']));
    }

    public function test_volver_no_puede_llevar_a_otro_sitio_ni_traer_listas(): void
    {
        $b = $this->crearProducto('B-1', 'Producto B');

        foreach (['https://otro-sitio.com/robar', '//otro-sitio.com', 'buscar[]=x&page[]=2'] as $volver) {
            $this->patch(route('admin.productos.update', $b), $this->datosFormulario($b, ['volver' => $volver]))
                ->assertRedirect(route('admin.productos.index') . "#producto-{$b->id}");
        }
    }

    public function test_sin_volver_va_a_la_lista_sin_filtros(): void
    {
        $b = $this->crearProducto('B-1', 'Producto B');

        $this->patch(route('admin.productos.update', $b), $this->datosFormulario($b))
            ->assertRedirect(route('admin.productos.index') . "#producto-{$b->id}");
    }
}
