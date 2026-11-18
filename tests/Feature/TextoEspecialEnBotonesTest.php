<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\TipoProducto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Js;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * Textos con apóstrofes, comillas o saltos de línea no deben romper los
 * onclick/onsubmit (antes: "D'Angelo" dejaba el botón de abono sin funcionar
 * y un nombre con ' hacía que "Desactivar" no pidiera confirmación).
 *
 * Los valores se pasan a JavaScript con @js() — se compara contra Js::from(),
 * que es exactamente lo que genera esa directiva.
 */
class TextoEspecialEnBotonesTest extends TestCase
{
    use RefreshDatabase;
    use CreaDatosDeReparacion;

    private const DESCRIPCION_MULTILINEA = "Línea 1\nLínea 2 con 'comillas' y \"dobles\"";

    public function test_el_boton_de_abono_no_lleva_el_nombre_del_cliente_en_el_onclick(): void
    {
        $usuario    = $this->crearUsuario();
        $reparacion = $this->crearReparacion($usuario, ['nombre_cliente' => "D'Angelo \"El Mono\""]);

        $html = $this->actingAs($usuario)
            ->get(route('admin.reparaciones.index', ['estado' => 'todos']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('onclick="abrirModalAbono(this)"', $html);
        $this->assertStringNotContainsString("abrirModalAbono({$reparacion->id},", $html);
    }

    public function test_categorias_pasan_nombre_y_descripcion_escapados(): void
    {
        $usuario   = $this->crearUsuario();
        $categoria = Categoria::create([
            'nombre'      => "Accesorios D'Luxe",
            'descripcion' => self::DESCRIPCION_MULTILINEA,
            'estado'      => 'activo',
        ]);

        $html = $this->actingAs($usuario)->get(route('admin.categorias.index'))->assertOk()->getContent();

        $this->assertStringContainsString(
            "abrirEditarCategoria({$categoria->id}, " . Js::from($categoria->nombre) . ', ' . Js::from($categoria->descripcion) . ')',
            $html
        );
        $this->assertStringContainsString(
            'return confirm(' . Js::from("¿Desactivar la categoría «{$categoria->nombre}»? Esto la ocultará del inventario.") . ')',
            $html
        );
        $this->assertStringNotContainsString("confirm('¿Desactivar", $html);
    }

    public function test_productos_pasan_sus_textos_escapados(): void
    {
        $usuario   = $this->crearUsuario();
        $categoria = Categoria::create(['nombre' => 'Accesorios de Carga', 'estado' => 'activo']);
        $producto  = Producto::create([
            'categoria_id'  => $categoria->id,
            'codigo'        => "AB'12",
            'nombre'        => "Cargador Apple's 20W",
            'descripcion'   => self::DESCRIPCION_MULTILINEA,
            'marca'         => "Apple's",
            'referencia'    => "A'1",
            'precio_compra' => 20000,
            'precio_venta'  => 45000,
            'stock'         => 5,
            'stock_minimo'  => 1,
            'estado'        => 'activo',
        ]);

        $html = $this->actingAs($usuario)->get(route('admin.productos.index'))->assertOk()->getContent();

        $this->assertStringContainsString("abrirAjusteStock({$producto->id}, " . Js::from($producto->nombre) . ')', $html);
        $this->assertStringContainsString(Js::from($producto->descripcion)->toHtml(), $html);
        $this->assertStringContainsString(Js::from($producto->codigo)->toHtml(), $html);
        $this->assertStringContainsString(
            'return confirm(' . Js::from("¿Desactivar «{$producto->nombre}»?") . ')',
            $html
        );
        $this->assertStringNotContainsString("confirm('¿Desactivar", $html);
    }

    public function test_tipos_de_producto_pasan_el_nombre_escapado(): void
    {
        $usuario = $this->crearUsuario();
        $tipo    = TipoProducto::create(['nombre' => "Original D'Fábrica"]);

        $html = $this->actingAs($usuario)->get(route('admin.settings.index'))->assertOk()->getContent();

        $this->assertStringContainsString("abrirModalEditar({$tipo->id}, " . Js::from($tipo->nombre) . ')', $html);
        $this->assertStringContainsString("abrirModalEliminar({$tipo->id}, " . Js::from($tipo->nombre) . ', 0)', $html);
    }
}
