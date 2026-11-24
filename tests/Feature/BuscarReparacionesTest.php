<?php

namespace Tests\Feature;

use App\Models\Reparacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * Búsqueda de la lista de Reparaciones (ReparacionController::index y Reparacion::scopeBuscar).
 *
 * Con la búsqueda en vivo se busca desde la tercera letra, y esas primeras letras traen
 * cientos: por eso va por páginas de 25. Cada palabra se busca por separado (marca y
 * modelo, nombre en cualquier orden), y un número se compara con los dígitos del teléfono,
 * que se guarda tal como se escribió ("310 123 4567").
 */
class BuscarReparacionesTest extends TestCase
{
    use RefreshDatabase;
    use CreaDatosDeReparacion;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = $this->crearUsuario();
        $this->actingAs($this->usuario);
    }

    /** La lista con esa búsqueda, entre todas las reparaciones de este año. */
    private function buscar(string $texto, array $extra = [])
    {
        return $this->get(route('admin.reparaciones.index', ['estado' => 'todos', 'periodo' => 'anio', 'buscar' => $texto] + $extra));
    }

    private function encontradas(string $texto): array
    {
        return $this->idsEnLaLista($this->buscar($texto)->assertOk()->getContent());
    }

    private function crearSamsungs(int $cuantas): void
    {
        for ($n = 1; $n <= $cuantas; $n++) {
            $this->crearReparacion($this->usuario, ['nombre_cliente' => "Cliente {$n}", 'marca' => 'Samsung']);
        }
    }

    // ── Páginas ──

    public function test_con_busqueda_va_de_a_25_y_los_enlaces_de_pagina_llevan_la_busqueda(): void
    {
        // Antes traía todas: con 3.000 reparaciones, "sam" bajaba 706 filas y 4 MB
        $this->crearSamsungs(30);
        $this->crearReparacion($this->usuario, ['nombre_cliente' => 'Otra marca', 'marca' => 'Xiaomi', 'modelo' => 'Redmi 13C']);

        $html = $this->buscar('Samsung')->assertOk()->getContent();

        $this->assertCount(25, $this->idsEnLaLista($html));
        // El total de lo encontrado, no lo de esta página
        $this->assertStringContainsString('<strong>30</strong> resultados', $html);
        $this->assertMatchesRegularExpression('#href="[^"]*buscar=Samsung[^"]*page=2"#', $html, 'El enlace a la página 2 lleva la búsqueda');

        $this->assertCount(5, $this->idsEnLaLista($this->buscar('Samsung', ['page' => 2])->assertOk()->getContent()));
    }

    public function test_las_creadas_en_el_mismo_segundo_salen_siempre_en_el_mismo_orden(): void
    {
        // Con el mismo created_at y sin desempate, el orden queda a criterio de la base, y la
        // paginación podía repetir o saltar reparaciones. El id desempata: la más nueva primero
        $this->crearSamsungs(30);
        Reparacion::query()->update(['created_at' => now()->startOfMinute()]);

        $ids = Reparacion::orderByDesc('id')->pluck('id')->all();

        $this->assertSame(array_slice($ids, 0, 25), $this->idsEnLaLista($this->buscar('Samsung')->getContent()));
        $this->assertSame(array_slice($ids, 25), $this->idsEnLaLista($this->buscar('Samsung', ['page' => 2])->getContent()));
    }

    public function test_una_pagina_que_ya_no_existe_lleva_a_la_ultima_con_la_busqueda(): void
    {
        $this->crearSamsungs(30);

        $destino = $this->buscar('Samsung', ['page' => 9])->assertRedirect()->headers->get('Location');

        $this->assertStringContainsString('buscar=Samsung', $destino);
        $this->assertStringContainsString('page=2', $destino);
    }

    // ── Palabras ──

    public function test_cada_palabra_se_busca_por_separado_y_en_cualquier_orden(): void
    {
        $laura = $this->crearReparacion($this->usuario, ['nombre_cliente' => 'Laura Gómez', 'marca' => 'Samsung', 'modelo' => 'A10', 'telefono_cliente' => '310 123 4567']);
        $pedro = $this->crearReparacion($this->usuario, ['nombre_cliente' => 'Pedro Ruiz', 'marca' => 'Xiaomi', 'modelo' => 'Redmi Note 12', 'telefono_cliente' => '320 555 0000']);

        $this->assertSame([$laura->id], $this->encontradas('Samsung A10'), 'marca y modelo');
        $this->assertSame([$pedro->id], $this->encontradas('Ruiz Pedro'), 'el nombre al revés');
        $this->assertSame([$pedro->id], $this->encontradas('Xiaomi Redmi'), 'marca y parte del modelo');
        $this->assertSame([], $this->encontradas('Samsung Redmi'), 'cada palabra tiene que estar');
    }

    // ── Teléfono ──

    public function test_un_numero_se_compara_con_los_digitos_del_telefono(): void
    {
        // El teléfono se guarda como se escribió: el ejemplo del campo es "310 123 4567"
        $laura = $this->crearReparacion($this->usuario, ['nombre_cliente' => 'Laura Gómez', 'telefono_cliente' => '310 123 4567']);
        $pedro = $this->crearReparacion($this->usuario, ['nombre_cliente' => 'Pedro Ruiz', 'telefono_cliente' => '(320) 555-0000']);

        foreach (['3101234567', '310-123-4567', '(310)', '310.123', '123 4567'] as $texto) {
            $this->assertSame([$laura->id], $this->encontradas($texto), "«{$texto}»");
        }

        $this->assertSame([$pedro->id], $this->encontradas('3205550000'), 'guardado con paréntesis y guion');
    }

    public function test_un_numero_tambien_busca_en_el_modelo(): void
    {
        $iphone = $this->crearReparacion($this->usuario, ['marca' => 'Apple', 'modelo' => 'iPhone 14', 'telefono_cliente' => '300 000 0000']);
        $this->crearReparacion($this->usuario, ['marca' => 'Samsung', 'modelo' => 'A10', 'telefono_cliente' => '320 555 0000']);

        $this->assertSame([$iphone->id], $this->encontradas('14'));
    }
}
