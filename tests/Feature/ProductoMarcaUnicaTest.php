<?php

namespace Tests\Feature;

use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\TipoProducto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Js;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * Productos repetidos: el nombre se puede repetir si la marca es distinta (dos "Batería
 * iPhone 14" de distinta marca). Mismo nombre y misma marca, o los dos sin marca, sigue
 * siendo repetido (ver App\Support\ProductoRepetido). Se revisa al validar y otra vez
 * justo antes de guardar, por si entre medio entró otro igual (un doble toque en Guardar).
 *
 * La base ya no tiene índice único: un producto desactivado no impide crearlo otra vez.
 * Las mayúsculas ("Deji" / "DEJI") no se prueban aquí: SQLite las distingue y MySQL no.
 */
class ProductoMarcaUnicaTest extends TestCase
{
    use RefreshDatabase;
    use CreaDatosDeReparacion;

    private Categoria $baterias;
    private Categoria $pantallas;
    private TipoProducto $original;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->crearUsuario());
        $this->baterias  = Categoria::create(['nombre' => 'Repuestos - Baterías', 'estado' => 'activo']);
        $this->pantallas = Categoria::create(['nombre' => 'Repuestos - Pantallas', 'estado' => 'activo']);
        $this->original  = TipoProducto::create(['nombre' => 'Original']);
    }

    /** Lo que mandan los formularios de crear y editar para una batería (sin marca). */
    private function datosBateria(array $cambios = []): array
    {
        return array_merge([
            'categoria_id'  => $this->baterias->id,
            'codigo'        => '',
            'nombre'        => 'Batería iPhone 14',
            'descripcion'   => '',
            'marca'         => '',
            'precio_compra' => '',
            'precio_venta'  => '90000',
            'stock'         => '3',
            'stock_minimo'  => '1',
        ], $cambios);
    }

    /** Lo que manda el formulario para una pantalla con tipo y con marco. */
    private function datosPantalla(array $cambios = []): array
    {
        return array_merge([
            'categoria_id'     => $this->pantallas->id,
            'tipo_producto_id' => $this->original->id,
            'codigo'           => '',
            'nombre'           => 'Pantalla Xiaomi 15C',
            'descripcion'      => '',
            'marco'            => '1',
            'marca'            => 'Xiaomi',
            'referencia'       => '15C',
            'precio_compra'    => '',
            'precio_venta'     => '180000',
            'stock'            => '2',
            'stock_minimo'     => '1',
        ], $cambios);
    }

    private function crearBateria(?string $marca, string $nombre = 'Batería iPhone 14'): Producto
    {
        return Producto::create([
            'categoria_id' => $this->baterias->id,
            'nombre'       => $nombre,
            'marca'        => $marca,
            'precio_venta' => 90000,
            'stock'        => 3,
            'stock_minimo' => 1,
            'estado'       => 'activo',
        ]);
    }

    private function crearPantalla15C(bool $marco, string $estado = 'activo'): Producto
    {
        return Producto::create([
            'categoria_id'     => $this->pantallas->id,
            'tipo_producto_id' => $this->original->id,
            'nombre'           => 'Pantalla Xiaomi 15C',
            'marco'            => $marco,
            'marca'            => 'Xiaomi',
            'referencia'       => '15C',
            'precio_venta'     => 180000,
            'stock'            => 2,
            'stock_minimo'     => 1,
            'estado'           => $estado,
        ]);
    }

    // ── Crear ──

    public function test_mismo_nombre_con_distinta_marca_se_puede_crear(): void
    {
        foreach (['Deji', 'Ampsentrix'] as $marca) {
            $this->post(route('admin.productos.store'), $this->datosBateria(['marca' => $marca]))
                ->assertRedirect(route('admin.productos.index'))
                ->assertSessionHasNoErrors()
                ->assertSessionHas('success')
                ->assertSessionMissing('error');
        }

        $this->assertSame(['Ampsentrix', 'Deji'], Producto::where('nombre', 'Batería iPhone 14')->orderBy('marca')->pluck('marca')->all());
    }

    public function test_mismo_nombre_y_misma_marca_se_rechaza_y_dice_contra_que_choco(): void
    {
        $this->crearBateria('Deji');
        $lista = route('admin.productos.index');

        $this->from($lista)
            ->post(route('admin.productos.store'), $this->datosBateria(['marca' => 'Deji']))
            ->assertRedirect($lista)
            ->assertSessionHasErrors(['nombre' => 'Ya existe «Batería iPhone 14» de la marca «Deji».']);

        $this->assertSame(1, Producto::count());
    }

    public function test_dos_con_el_mismo_nombre_y_sin_marca_siguen_chocando(): void
    {
        $this->crearBateria(null);

        $this->post(route('admin.productos.store'), $this->datosBateria())
            ->assertSessionHasErrors(['nombre' => 'Ya existe «Batería iPhone 14» sin marca. Si es de otra marca, escríbela en el campo Marca.']);

        $this->assertSame(1, Producto::count());
    }

    public function test_una_marca_guardada_vacia_cuenta_como_sin_marca(): void
    {
        $this->crearBateria('');

        $this->post(route('admin.productos.store'), $this->datosBateria())
            ->assertSessionHasErrors(['nombre']);

        $this->assertSame(1, Producto::count());
    }

    public function test_uno_con_marca_y_otro_sin_marca_pueden_convivir(): void
    {
        // Ya está la de Deji: se crea la que no tiene marca
        $this->crearBateria('Deji');
        $this->post(route('admin.productos.store'), $this->datosBateria())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        // Ya está la que no tiene marca: se crea la de Deji
        $this->crearBateria(null, 'Batería iPhone 13');
        $this->post(route('admin.productos.store'), $this->datosBateria(['nombre' => 'Batería iPhone 13', 'marca' => 'Deji']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame(4, Producto::count());
    }

    // ── Editar ──

    public function test_al_editar_no_puede_quedar_igual_a_otro_pero_si_guardarse_sin_cambios(): void
    {
        $deji       = $this->crearBateria('Deji');
        $ampsentrix = $this->crearBateria('Ampsentrix');
        $lista      = route('admin.productos.index', ['vista' => 'lista']);

        $this->from($lista)
            ->patch(route('admin.productos.update', $ampsentrix), $this->datosBateria(['marca' => 'Deji']))
            ->assertRedirect($lista)
            ->assertSessionHasErrorsIn('editarProducto', ['nombre' => 'Ya existe «Batería iPhone 14» de la marca «Deji».']);

        $this->assertSame('Ampsentrix', $ampsentrix->fresh()->marca);

        // El que se edita no choca consigo mismo; el aviso lleva la marca
        $this->patch(route('admin.productos.update', $deji), $this->datosBateria(['marca' => 'Deji']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Producto "Batería iPhone 14 (Deji)" actualizado con éxito.')
            ->assertSessionMissing('error');
    }

    // ── Doble toque en Guardar: otro igual se guarda entre la validación y el guardado ──

    public function test_si_otro_igual_se_guarda_justo_despues_de_validar_no_queda_repetido(): void
    {
        Storage::fake('public');
        $lista = route('admin.productos.index');

        // Como el segundo envío de un doble toque: el otro termina de guardarse justo
        // después de que este pasó la validación, cuando ella ya no lo puede ver
        $this->app->afterResolving(StoreProductoRequest::class, fn () => $this->crearBateria('Deji'));

        $this->from($lista)
            ->post(route('admin.productos.store'), $this->datosBateria([
                'marca'    => 'Deji',
                'imagenes' => [UploadedFile::fake()->image('bateria.jpg', 800, 600)],
            ]))
            ->assertRedirect($lista)
            ->assertSessionHasErrors(['nombre' => 'Ya existe «Batería iPhone 14» de la marca «Deji».'])
            ->assertSessionMissing('error');

        $this->assertSame(1, Producto::count());
        $this->assertSame(0, ProductoImagen::count());
        $this->assertSame([], Storage::disk('public')->allFiles(), 'La foto de este envío no debía quedar guardada');
    }

    public function test_al_editar_si_otro_igual_se_guarda_justo_despues_de_validar_vuelve_al_modal_con_el_aviso(): void
    {
        $ampsentrix = $this->crearBateria('Ampsentrix');
        $lista      = route('admin.productos.index');

        $this->app->afterResolving(UpdateProductoRequest::class, fn () => $this->crearBateria('Deji'));

        // Como si fallara la validación de editar: su bolsa de errores y el producto para reabrir el modal
        $this->from($lista)
            ->patch(route('admin.productos.update', $ampsentrix), $this->datosBateria(['marca' => 'Deji']))
            ->assertRedirect($lista)
            ->assertSessionHasErrorsIn('editarProducto', ['nombre' => 'Ya existe «Batería iPhone 14» de la marca «Deji».'])
            ->assertSessionHas(UpdateProductoRequest::PRODUCTO_CON_ERRORES, $ampsentrix->id)
            ->assertSessionMissing('error');

        $this->assertSame('Ampsentrix', $ampsentrix->fresh()->marca);
    }

    // ── Desactivados (antes los bloqueaba el índice único de la base) ──

    public function test_un_producto_desactivado_no_impide_crearlo_otra_vez(): void
    {
        $this->post(route('admin.productos.store'), $this->datosPantalla())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->delete(route('admin.productos.destroy', Producto::sole()))
            ->assertSessionHas('success', 'Producto "Pantalla Xiaomi 15C" desactivado.');

        // Antes: el formulario la dejaba pasar y la base la rechazaba (SQLSTATE[23000])
        $this->post(route('admin.productos.store'), $this->datosPantalla())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success')
            ->assertSessionMissing('error');

        $this->assertSame(2, Producto::count());
        $this->assertSame(1, Producto::activos()->count());
    }

    public function test_editar_para_quedar_igual_a_uno_desactivado_funciona(): void
    {
        $this->crearPantalla15C(marco: true, estado: 'inactivo');
        $sinMarco = $this->crearPantalla15C(marco: false);

        $this->patch(route('admin.productos.update', $sinMarco), $this->datosPantalla(['marco' => '1']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success')
            ->assertSessionMissing('error');

        $this->assertTrue($sinMarco->fresh()->marco);
    }

    // ── Pantallas ──

    public function test_las_pantallas_siguen_siendo_unicas_por_nombre_tipo_y_marco(): void
    {
        $this->post(route('admin.productos.store'), $this->datosPantalla())
            ->assertSessionHasNoErrors();

        $this->post(route('admin.productos.store'), $this->datosPantalla())
            ->assertSessionHasErrors(['nombre' => 'Ya existe «Pantalla Xiaomi 15C» de la marca «Xiaomi», con marco.']);

        // Sin marco es otro producto
        $this->post(route('admin.productos.store'), $this->datosPantalla(['marco' => '0']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame(2, Producto::count());
    }

    // ── Lista, tarjetas, buscador y orden ──

    public function test_nombre_con_marca_y_marca_visible(): void
    {
        $deji     = $this->crearBateria('Deji');
        $sinMarca = $this->crearBateria(null, 'Batería iPhone 13');
        $pantalla = $this->crearPantalla15C(marco: true);

        $this->assertSame('Deji', $deji->marca_visible);
        $this->assertSame('Batería iPhone 14 (Deji)', $deji->nombre_con_marca);

        $this->assertNull($sinMarca->marca_visible);
        $this->assertSame('Batería iPhone 13', $sinMarca->nombre_con_marca);

        // En pantallas la marca es la del celular y ya va en el nombre
        $this->assertNull($pantalla->marca_visible);
        $this->assertSame('Pantalla Xiaomi 15C', $pantalla->nombre_con_marca);
    }

    public function test_la_lista_y_las_tarjetas_muestran_la_marca_menos_en_pantallas(): void
    {
        // Deji se crea primero: si sale después de Ampsentrix, es porque se ordena por marca
        $deji       = $this->crearBateria('Deji');
        $ampsentrix = $this->crearBateria('Ampsentrix');
        $pantalla   = $this->crearPantalla15C(marco: true);

        foreach (['tarjetas', 'lista'] as $vista) {
            $html = $this->get(route('admin.productos.index', ['vista' => $vista]))->assertOk()->getContent();

            $this->assertSame(1, preg_match_all('#title="Marca">\s*Deji\s*</span>#', $html), "Vista {$vista}");
            $this->assertSame(1, preg_match_all('#title="Marca">\s*Ampsentrix\s*</span>#', $html), "Vista {$vista}");
            $this->assertSame(0, preg_match_all('#title="Marca">\s*Xiaomi#', $html), "Vista {$vista}: la pantalla ya la tiene en el nombre");

            $this->assertStringContainsString("abrirAjusteStock({$deji->id}, " . Js::from('Batería iPhone 14 (Deji)') . ')', $html, "Vista {$vista}");
            $this->assertStringContainsString("abrirAjusteStock({$pantalla->id}, " . Js::from('Pantalla Xiaomi 15C') . ')', $html, "Vista {$vista}");
            $this->assertStringContainsString('return confirm(' . Js::from('¿Desactivar «Batería iPhone 14 (Deji)»?') . ')', $html, "Vista {$vista}");

            $this->assertLessThan(
                strpos($html, "abrirAjusteStock({$deji->id},"),
                strpos($html, "abrirAjusteStock({$ampsentrix->id},"),
                "Vista {$vista}: con el mismo nombre, Ampsentrix va antes que Deji"
            );
        }
    }

    public function test_el_buscador_encuentra_por_marca(): void
    {
        $deji       = $this->crearBateria('Deji');
        $ampsentrix = $this->crearBateria('Ampsentrix');

        $html = $this->get(route('admin.productos.index', ['vista' => 'lista', 'buscar' => 'Deji']))->assertOk()->getContent();

        $this->assertStringContainsString("abrirAjusteStock({$deji->id},", $html);
        $this->assertStringNotContainsString("abrirAjusteStock({$ampsentrix->id},", $html);
    }

    public function test_el_buscador_encuentra_con_nombre_y_marca_juntos_en_cualquier_orden(): void
    {
        $deji       = $this->crearBateria('Deji');
        $ampsentrix = $this->crearBateria('Ampsentrix');
        $buscar     = fn (string $texto) => $this->get(route('admin.productos.index', ['vista' => 'lista', 'buscar' => $texto]))
            ->assertOk()->getContent();

        foreach (['iPhone 14 Deji', 'Deji iPhone', '  deji   14 '] as $texto) {
            $html = $buscar($texto);

            $this->assertStringContainsString("abrirAjusteStock({$deji->id},", $html, "«{$texto}»");
            $this->assertStringNotContainsString("abrirAjusteStock({$ampsentrix->id},", $html, "«{$texto}»");
        }

        // Tienen que estar todas las palabras: ninguna es Deji y Ampsentrix a la vez
        $html = $buscar('Deji Ampsentrix');

        $this->assertStringNotContainsString("abrirAjusteStock({$deji->id},", $html);
        $this->assertStringNotContainsString("abrirAjusteStock({$ampsentrix->id},", $html);
    }

    // ── Formulario ──

    public function test_crear_con_errores_en_pantallas_vuelve_con_sus_campos_a_la_vista(): void
    {
        $this->crearPantalla15C(marco: true);
        $lista = route('admin.productos.index');

        $this->from($lista)
            ->post(route('admin.productos.store'), $this->datosPantalla())
            ->assertSessionHasErrors(['nombre']);

        $html = $this->get($lista)->assertOk()->getContent();

        // Vuelve con la categoría y lo escrito, y al cargar muestra los campos de pantallas
        $this->assertMatchesRegularExpression("#<option value=\"{$this->pantallas->id}\" selected>#", $html);
        $this->assertMatchesRegularExpression('#id="crear-marca"[^>]*value="Xiaomi"#', $html);
        $this->assertMatchesRegularExpression(
            "#mostrarCamposSegunCategoria\(document\.getElementById\('crear-categoria_id'\)\.value, 'crear'\)\)\s*\{\s*generarNombrePantalla\('crear'\);#",
            $html
        );
    }
}
