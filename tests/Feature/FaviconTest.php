<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * Ícono de la pestaña: todas las páginas traen el logo (components/favicon.blade.php)
 * y los archivos del ícono en public/ son imágenes de verdad.
 */
class FaviconTest extends TestCase
{
    use RefreshDatabase;
    use CreaDatosDeReparacion;

    private function traeElIcono(string $pagina, string $html): void
    {
        $this->assertMatchesRegularExpression('#<link rel="icon" href="[^"]*/favicon\.ico\?v=\d+">#', $html, "$pagina no trae el favicon");
        $this->assertMatchesRegularExpression('#<link rel="apple-touch-icon" href="[^"]*/apple-touch-icon\.png\?v=\d+">#', $html, "$pagina no trae el apple-touch-icon");
    }

    public function test_la_pagina_publica_y_las_de_acceso_traen_el_icono(): void
    {
        $paginas = [
            route('landing'),
            route('login'),
            route('password.request'),
            route('password.reset', 'token-de-prueba'),
        ];

        foreach ($paginas as $pagina) {
            $this->traeElIcono($pagina, $this->get($pagina)->assertOk()->getContent());
        }
    }

    public function test_el_panel_trae_el_icono(): void
    {
        $this->actingAs($this->crearUsuario());
        $pagina = route('admin.categorias.index');

        $this->traeElIcono($pagina, $this->get($pagina)->assertOk()->getContent());
    }

    public function test_los_archivos_del_icono_son_imagenes_de_verdad(): void
    {
        // El proyecto base de Laravel trae un favicon.ico vacío: que no vuelva por accidente
        $this->assertGreaterThan(0, filesize(public_path('favicon.ico')), 'public/favicon.ico está vacío');
        $this->assertSame(IMAGETYPE_ICO, getimagesize(public_path('favicon.ico'))[2]);

        [$ancho, $alto, $tipo] = getimagesize(public_path('apple-touch-icon.png'));
        $this->assertSame([180, 180, IMAGETYPE_PNG], [$ancho, $alto, $tipo]);
    }
}
