<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * Layout del panel: lo que evita que la página salte o parpadee al cargar.
 *
 * - El sidebar mini se decide en el <head>, antes de dibujar (si se aplicara al
 *   final, se vería abrirse y cerrarse en cada cambio de sección).
 * - Los mensajes de guardado y error flotan (components/avisos-sesion): no
 *   empujan la página al irse.
 */
class LayoutPanelTest extends TestCase
{
    use RefreshDatabase;
    use CreaDatosDeReparacion;

    private function paginaDelPanel(string $ruta, array $sesion = []): string
    {
        $this->actingAs($this->crearUsuario());

        return $this->withSession($sesion)->get(route($ruta))->assertOk()->getContent();
    }

    public function test_la_preferencia_del_sidebar_se_aplica_antes_de_dibujar(): void
    {
        $html = $this->paginaDelPanel('admin.categorias.index');

        // El <head> marca el <html> con la preferencia guardada; la CSS hace el resto
        $aplica = strpos($html, "document.documentElement.classList.add('sidebar-mini')");
        $this->assertNotFalse($aplica, 'El layout no aplica la preferencia del sidebar en el <html>');
        $this->assertLessThan(strpos($html, '<body'), $aplica, 'La preferencia del sidebar se aplica después de empezar a dibujar');

        // El sidebar no trae en el HTML un ancho que después haya que corregir con JS
        preg_match('#<aside id="sidebar"[^>]*>#', $html, $sidebar);
        $this->assertStringNotContainsString('sidebar-expanded', $sidebar[0]);
    }

    public function test_el_menu_no_tiene_enlaces_que_no_llevan_a_ningun_lado(): void
    {
        $html = $this->paginaDelPanel('admin.categorias.index');

        $this->assertMatchesRegularExpression('#<aside id="sidebar".*?</aside>#s', $html);
        preg_match('#<aside id="sidebar".*?</aside>#s', $html, $sidebar);
        $this->assertStringNotContainsString('href="#"', $sidebar[0]);
    }

    public function test_un_mensaje_de_guardado_flota_y_sale_una_sola_vez(): void
    {
        $html = $this->paginaDelPanel('admin.categorias.index', ['success' => 'Categoría de prueba guardada']);

        $this->assertSame(1, substr_count($html, 'Categoría de prueba guardada'));
        $this->assertMatchesRegularExpression(
            '#id="contenedor-notificaciones".*?data-aviso-sesion="exito".*?Categoría de prueba guardada#s',
            $html
        );
    }

    public function test_los_mensajes_de_perfil_tambien_flotan(): void
    {
        $html = $this->paginaDelPanel('admin.perfil', ['success_perfil' => 'Información de prueba actualizada']);

        $this->assertSame(1, substr_count($html, 'Información de prueba actualizada'));
        $this->assertMatchesRegularExpression(
            '#id="contenedor-notificaciones".*?data-aviso-sesion="exito".*?Información de prueba actualizada#s',
            $html
        );
    }

    public function test_un_error_se_queda_hasta_cerrarlo_con_la_x(): void
    {
        $html = $this->paginaDelPanel('admin.productos.index', ['error' => 'Error de prueba al guardar']);

        $this->assertSame(1, substr_count($html, 'Error de prueba al guardar'));
        $this->assertMatchesRegularExpression(
            '#id="contenedor-notificaciones".*?data-aviso-sesion="error".*?Error de prueba al guardar.*?data-cerrar#s',
            $html
        );
    }
}
