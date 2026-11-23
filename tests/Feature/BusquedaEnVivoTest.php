<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * Búsqueda en vivo (resources/js/busqueda-en-vivo.js): mientras se escribe, el JS pide la
 * misma página con la búsqueda nueva y reemplaza cada parte data-en-vivo="nombre" por la
 * de la respuesta. Si una parte faltara en algún caso (sin filtros, sin resultados...),
 * el reemplazo la perdería: por eso se revisa que estén todas, siempre, una sola vez.
 *
 * El JS en sí se prueba en el navegador (ver CLAUDE.md, "Búsqueda en vivo").
 */
class BusquedaEnVivoTest extends TestCase
{
    use RefreshDatabase;
    use CreaDatosDeReparacion;

    private const PARTES_PRODUCTOS = [
        'total', 'stock-bajo', 'limpiar-filtros', 'vista', 'indicador', 'resultados', 'stock-por-producto', 'volver',
    ];

    private const PARTES_REPARACIONES = ['limpiar-busqueda', 'limpiar-filtros', 'indicador', 'resultados', 'paginacion'];

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = $this->crearUsuario();
        $this->actingAs($this->usuario);
    }

    private function crearProducto(Categoria $categoria, string $nombre, int $stock, int $minimo): Producto
    {
        return Producto::create([
            'categoria_id' => $categoria->id,
            'nombre'       => $nombre,
            'precio_venta' => 45000,
            'stock'        => $stock,
            'stock_minimo' => $minimo,
            'estado'       => 'activo',
        ]);
    }

    /** Revisa que la página tenga el formulario en vivo y cada parte una sola vez. */
    private function assertTieneLasPartes(array $esperadas, string $html, string $caso): void
    {
        $this->assertStringContainsString('data-busqueda-en-vivo', $html, $caso);

        preg_match_all('/data-en-vivo="([^"]+)"/', $html, $encontradas);
        $cuantas = array_count_values($encontradas[1]);

        foreach ($esperadas as $parte) {
            $this->assertSame(1, $cuantas[$parte] ?? 0, "{$caso}: la parte «{$parte}» tiene que estar una vez");
        }
    }

    // ── Productos ──

    public function test_productos_tiene_todas_las_partes_en_cada_caso(): void
    {
        $accesorios = Categoria::create(['nombre' => 'Accesorios', 'estado' => 'activo']);
        $this->crearProducto($accesorios, 'Cargador 20W', 1, 3);

        foreach ([
            'sin filtros'     => [],
            'con búsqueda'    => ['buscar' => 'Cargador'],
            'sin resultados'  => ['buscar' => 'zzz'],
            'con stock bajo'  => ['stock_bajo' => '1'],
            'con categoría'   => ['categoria' => $accesorios->id, 'buscar' => 'Carg'],
            'en lista'        => ['vista' => 'lista', 'buscar' => 'Cargador'],
        ] as $caso => $query) {
            $html = $this->get(route('admin.productos.index', $query))->assertOk()->getContent();

            $this->assertTieneLasPartes(self::PARTES_PRODUCTOS, $html, $caso);
        }
    }

    public function test_el_stock_de_los_productos_de_la_pagina_va_en_su_json(): void
    {
        // Sin esto, "Ajustar stock" de un producto traído por la búsqueda mostraba 0 unidades
        $accesorios = Categoria::create(['nombre' => 'Accesorios', 'estado' => 'activo']);
        $cargador   = $this->crearProducto($accesorios, 'Cargador 20W', 7, 2);
        $cable      = $this->crearProducto($accesorios, 'Cargador de carro', 0, 3);
        $this->crearProducto($accesorios, 'Vidrio templado', 4, 1);

        $html = $this->get(route('admin.productos.index', ['buscar' => 'Cargador']))->assertOk()->getContent();

        $this->assertSame(1, preg_match('#<script type="application/json" id="stock-por-producto" data-en-vivo="stock-por-producto">(.*?)</script>#s', $html, $json));
        $this->assertSame(
            [$cargador->id => ['stock' => 7, 'min' => 2], $cable->id => ['stock' => 0, 'min' => 3]],
            json_decode($json[1], true)
        );

        // Sin productos es un objeto vacío (el JS le va sumando los que trae cada búsqueda)
        $html = $this->get(route('admin.productos.index', ['buscar' => 'zzz']))->assertOk()->getContent();
        $this->assertStringContainsString('data-en-vivo="stock-por-producto">{}</script>', $html);
    }

    public function test_con_stock_bajo_activo_el_formulario_lo_conserva_al_buscar(): void
    {
        // Activo, el botón se vuelve un enlace y no se envía: sin el campo oculto, buscar o
        // cambiar la categoría perdía el filtro
        $html = $this->get(route('admin.productos.index', ['stock_bajo' => '1']))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#<form[^>]*id="form-filtros".*<input type="hidden" name="stock_bajo" value="1">.*</form>#s', $html);

        $html = $this->get(route('admin.productos.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('<input type="hidden" name="stock_bajo"', $html);
    }

    public function test_volver_del_modal_de_editar_trae_la_busqueda_actual(): void
    {
        $accesorios = Categoria::create(['nombre' => 'Accesorios', 'estado' => 'activo']);

        $html = $this->get(route('admin.productos.index', ['categoria' => $accesorios->id, 'buscar' => 'Cargador']))
            ->assertOk()->getContent();

        $this->assertStringContainsString(
            "name=\"volver\" value=\"buscar=Cargador&amp;categoria={$accesorios->id}\" data-en-vivo=\"volver\"",
            $html
        );
    }

    // ── Reparaciones ──

    public function test_reparaciones_tiene_todas_las_partes_en_cada_caso(): void
    {
        $this->crearReparacion($this->usuario, ['nombre_cliente' => 'Laura Gómez']);

        foreach ([
            'sin búsqueda'   => [],
            'con búsqueda'   => ['buscar' => 'Laura'],
            'sin resultados' => ['buscar' => 'zzz'],
            'todos'          => ['estado' => 'todos', 'periodo' => 'anio'],
        ] as $caso => $query) {
            $html = $this->get(route('admin.reparaciones.index', $query))->assertOk()->getContent();

            $this->assertTieneLasPartes(self::PARTES_REPARACIONES, $html, $caso);
        }
    }

    public function test_sin_resultados_el_mensaje_depende_de_si_hay_busqueda(): void
    {
        // Con la búsqueda en vivo este mensaje sale a cada rato: no es que no haya reparaciones
        $this->get(route('admin.reparaciones.index', ['buscar' => 'zzz']))
            ->assertOk()
            ->assertSee('No se encontraron reparaciones con esa búsqueda')
            ->assertDontSee('No hay reparaciones registradas');

        $this->get(route('admin.reparaciones.index'))
            ->assertOk()
            ->assertSee('No hay reparaciones registradas')
            ->assertDontSee('No se encontraron reparaciones con esa búsqueda');
    }
}
