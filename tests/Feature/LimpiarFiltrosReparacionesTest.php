<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * El botón «Limpiar filtros» de Reparaciones.
 *
 * Vuelve a la página de inicio: «En proceso», sin búsqueda. Sale solo cuando lo que se ve es
 * otra cosa: otro estado (Todos, Arreglados o Entregados, con cualquier periodo) o algo escrito
 * en el buscador, aunque sea una letra (el campo y la × la muestran). En «En proceso» el periodo
 * y las fechas no cuentan: ahí no se filtra por fecha, y al pasar de «Personalizado» a «En
 * proceso» las fechas siguen en la URL (los campos ocultos se envían igual).
 *
 * Antes salía también en el inicio, y ahí no hacía nada: llevaba a la misma página.
 */
class LimpiarFiltrosReparacionesTest extends TestCase
{
    use RefreshDatabase;
    use CreaDatosDeReparacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->crearUsuario());
    }

    /** [query string, si sale el botón]. */
    public static function casos(): array
    {
        return [
            'inicio, sin nada'                          => ['',                                                                                      false],
            'En proceso elegido en el select'           => ['estado=en_proceso&periodo=semestre&buscar=',                                            false],
            'En proceso con fechas que quedaron de Personalizado'
                                                        => ['estado=en_proceso&periodo=personalizado&desde=2026-03-10&hasta=2026-03-12&buscar=', false],
            'estado desconocido (queda En proceso)'     => ['estado=xyz',                                                                            false],
            'Todos'                                     => ['estado=todos',                                                                          true],
            'Todos · Últimos 6 meses'                   => ['estado=todos&periodo=semestre',                                                         true],
            'Todos · Este mes'                          => ['estado=todos&periodo=mes',                                                              true],
            'Arreglados'                                => ['estado=arreglado',                                                                      true],
            'En proceso con búsqueda'                   => ['buscar=Laura',                                                                          true],
            'En proceso con una letra escrita'          => ['estado=en_proceso&buscar=s',                                                            true],
        ];
    }

    /**
     * Sale solo fuera del inicio, y siempre lleva al inicio. Como en el inicio no sale, nunca
     * lleva a la misma página.
     */
    #[DataProvider('casos')]
    public function test_sale_solo_fuera_del_inicio_y_lleva_al_inicio(string $query, bool $sale): void
    {
        $html = $this->get('/admin/reparaciones?' . $query)->assertOk()->getContent();

        $this->assertSame($sale ? route('admin.reparaciones.index') : null, $this->enlaceLimpiarFiltros($html));
    }

    /**
     * A dónde lleva «Limpiar filtros», o null si no sale. La parte tiene que estar siempre (la
     * búsqueda en vivo la reemplaza): sin ella, los casos en que no sale pasarían sin probar nada.
     */
    private function enlaceLimpiarFiltros(string $html): ?string
    {
        $this->assertSame(1, preg_match('#<div data-en-vivo="limpiar-filtros"[^>]*>(.*?)</div>#s', $html, $parte),
            'Falta la parte data-en-vivo="limpiar-filtros"');

        if (! preg_match('#<a href="([^"]+)"[^>]*>.*?Limpiar filtros#s', $parte[1], $enlace)) {
            return null;
        }

        return html_entity_decode($enlace[1]);
    }
}
