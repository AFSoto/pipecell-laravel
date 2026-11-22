<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoImagen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * Productos en tarjetas o en lista: cuál se dibuja, cuántos van por página, que cada
 * equipo recuerde su elección (cookie) y que la lista tenga los mismos botones.
 */
class ProductosVistaTest extends TestCase
{
    use RefreshDatabase;
    use CreaDatosDeReparacion;

    private const COOKIE = 'productos_vista';

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->crearUsuario());
    }

    /** Crea productos activos con foto principal (códigos y nombres desde $desde). */
    private function crearProductos(int $cantidad, int $desde = 1): Producto
    {
        $categoria = Categoria::firstOrCreate(['nombre' => 'Repuestos - Pantallas'], ['estado' => 'activo']);

        foreach (range($desde, $desde + $cantidad - 1) as $n) {
            $producto = Producto::create([
                'categoria_id'  => $categoria->id,
                'codigo'        => sprintf('P-%03d', $n),
                'nombre'        => sprintf('Producto %03d', $n),
                'precio_compra' => 10000,
                'precio_venta'  => 20000,
                'stock'         => 5,
                'stock_minimo'  => 1,
                'estado'        => 'activo',
            ]);
            ProductoImagen::create(['producto_id' => $producto->id, 'path' => "productos/foto-{$n}.jpg", 'es_principal' => true]);
        }

        return $producto;
    }

    /** Cuántos productos se dibujaron: cada uno trae su botón de stock. */
    private function productosEnPantalla(string $html): int
    {
        return substr_count($html, 'id="stock-display-');
    }

    public function test_sin_elegir_muestra_tarjetas_de_a_12(): void
    {
        $this->crearProductos(13);

        $html = $this->get(route('admin.productos.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-vista="tarjetas"', $html);
        $this->assertSame(12, $this->productosEnPantalla($html));
    }

    public function test_la_lista_muestra_hasta_100_y_lo_recuerda_en_una_cookie(): void
    {
        $this->crearProductos(101);

        $respuesta = $this->get(route('admin.productos.index', ['vista' => 'lista']))->assertOk();

        $this->assertStringContainsString('data-vista="lista"', $respuesta->getContent());
        $this->assertSame(100, $this->productosEnPantalla($respuesta->getContent()));
        $respuesta->assertCookie(self::COOKIE, 'lista');
    }

    public function test_con_la_cookie_de_lista_abre_en_lista(): void
    {
        $this->crearProductos(3);

        $html = $this->withCookie(self::COOKIE, 'lista')->get(route('admin.productos.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-vista="lista"', $html);
    }

    public function test_se_puede_volver_a_tarjetas(): void
    {
        $this->crearProductos(3);

        $respuesta = $this->withCookie(self::COOKIE, 'lista')
            ->get(route('admin.productos.index', ['vista' => 'tarjetas']))
            ->assertOk();

        $this->assertStringContainsString('data-vista="tarjetas"', $respuesta->getContent());
        $respuesta->assertCookie(self::COOKIE, 'tarjetas');
    }

    public function test_un_valor_invalido_no_cambia_la_vista_ni_la_cookie(): void
    {
        $this->crearProductos(3);

        $respuesta = $this->withCookie(self::COOKIE, 'lista')
            ->get(route('admin.productos.index', ['vista' => 'otra']))
            ->assertOk();

        $this->assertStringContainsString('data-vista="lista"', $respuesta->getContent());
        $respuesta->assertCookieMissing(self::COOKIE);
    }

    public function test_la_fila_trae_la_miniatura_y_los_mismos_botones_que_la_tarjeta(): void
    {
        $producto = $this->crearProductos(1);

        $html = $this->get(route('admin.productos.index', ['vista' => 'lista']))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<img src="[^"]*storage/productos/foto-1\.jpg"[^>]*loading="lazy"#', $html);
        $this->assertStringContainsString("id=\"stock-display-{$producto->id}\"", $html);
        $this->assertMatchesRegularExpression("#abrirEditarProducto\(\s*{$producto->id},#", $html);
        $this->assertStringContainsString("abrirAjusteStock({$producto->id}, ", $html);
        $this->assertStringContainsString('action="' . route('admin.productos.destroy', $producto) . '"', $html);
    }

    public function test_las_consultas_no_crecen_con_la_cantidad_de_productos(): void
    {
        foreach (['tarjetas', 'lista'] as $vista) {
            DB::table('producto_imagenes')->delete();
            DB::table('productos')->delete();

            $this->crearProductos(2);
            $conDos = $this->consultasAlAbrir($vista);

            $this->crearProductos(8, 3);
            $conDiez = $this->consultasAlAbrir($vista);

            $this->assertSame($conDos, $conDiez, "En la vista {$vista} cada producto agrega consultas");
        }
    }

    private function consultasAlAbrir(string $vista): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('admin.productos.index', ['vista' => $vista]))->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }
}
