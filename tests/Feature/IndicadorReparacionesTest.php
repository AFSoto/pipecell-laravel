<?php

namespace Tests\Feature;

use App\Models\Reparacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * El indicador de la lista de Reparaciones (el texto debajo de los filtros) y la lista vacía.
 *
 * La búsqueda busca dentro del estado y del periodo elegidos, así que con búsqueda el
 * indicador dice entre cuáles buscó. Se busca desde 2 letras, como en el servidor: con una
 * sola letra (que llega al cambiar un select con algo escrito) no hay búsqueda.
 */
class IndicadorReparacionesTest extends TestCase
{
    use RefreshDatabase;
    use CreaDatosDeReparacion;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = $this->crearUsuario();
        $this->actingAs($this->usuario);
        $this->travelTo(Carbon::parse('2026-11-23 15:40:00'));
    }

    private function reparacion(string $cliente, string $fecha, string $estado = 'en_proceso'): Reparacion
    {
        $reparacion = $this->crearReparacion($this->usuario, ['nombre_cliente' => $cliente]);

        Reparacion::whereKey($reparacion->id)->update(['created_at' => $fecha, 'estado' => $estado]);

        return $reparacion;
    }

    private function pagina(array $filtros): string
    {
        return $this->get(route('admin.reparaciones.index', $filtros))->assertOk()->getContent();
    }

    /** El texto del indicador, sin etiquetas ni espacios de más. */
    private function indicador(string $html): string
    {
        preg_match('#data-en-vivo="indicador"[^>]*>(.*?)</p>#s', $html, $texto);

        return trim(preg_replace('/\s+/', ' ', strip_tags($texto[1] ?? '')));
    }

    /** [filtros, lo que dice el indicador al buscar «Juan»]. Hoy: 23 nov 2026. */
    public static function busquedas(): array
    {
        return [
            'todos, últimos 6 meses' => [['estado' => 'todos', 'periodo' => 'semestre'],
                '2 resultados para "Juan" entre todas las reparaciones de los últimos 6 meses (del 23/05/2026 a hoy)'],
            'entregadas, este mes' => [['estado' => 'entregado', 'periodo' => 'mes'],
                '1 resultado para "Juan" entre las reparaciones entregadas de este mes'],
            'en proceso: no filtra por fecha' => [['estado' => 'en_proceso', 'periodo' => 'semestre'],
                '1 resultado para "Juan" entre las reparaciones en proceso'],
        ];
    }

    #[DataProvider('busquedas')]
    public function test_con_busqueda_el_indicador_dice_entre_cuales_busco(array $filtros, string $esperado): void
    {
        $this->reparacion('Juan Entregado', '2026-11-10 10:00:00', 'entregado');   // este mes
        $this->reparacion('Juan Taller', '2025-01-10 10:00:00');                   // en proceso, de hace casi 2 años
        $this->reparacion('Juan Julio', '2026-07-01 10:00:00', 'arreglado');       // en 6 meses, no en este mes
        $this->reparacion('Pedro Ruiz', '2026-11-10 10:00:00', 'entregado');

        $html = $this->pagina($filtros + ['buscar' => 'Juan']);

        $this->assertSame($esperado, $this->indicador($html));
        $this->assertStringNotContainsString('en en proceso', $html);
    }

    /** Sin resultados, sugiere otro periodo solo si el periodo filtra (En proceso no filtra por fecha). */
    public function test_sin_resultados_sugiere_otro_periodo_si_el_periodo_filtra(): void
    {
        $this->reparacion('Juan Septiembre', '2026-09-10 10:00:00', 'entregado');

        // Todos / Este mes: Juan es de septiembre
        $html = $this->pagina(['estado' => 'todos', 'periodo' => 'mes', 'buscar' => 'Juan']);
        $this->assertSame('0 resultados para "Juan" entre todas las reparaciones de este mes', $this->indicador($html));
        $this->assertStringContainsString('No se encontraron reparaciones con esa búsqueda', $html);
        $this->assertStringContainsString('Prueba con el nombre, el teléfono, la marca o el modelo, o con otro periodo', $html);

        // En proceso: cambiar de periodo no ayudaría
        $html = $this->pagina(['estado' => 'en_proceso', 'buscar' => 'Juan']);
        $this->assertStringContainsString('No se encontraron reparaciones con esa búsqueda', $html);
        $this->assertStringContainsString('Prueba con el nombre, el teléfono, la marca o el modelo', $html);
        $this->assertStringNotContainsString('otro periodo', $html);
    }

    public static function unaLetra(): array
    {
        return ['una letra' => ['s'], 'una letra con tilde (dos bytes)' => ['ñ']];
    }

    /**
     * Con una letra escrita, cambiar el estado o el periodo envía el formulario entero. El
     * servidor no busca con una letra, y el indicador no puede decir «resultados para».
     */
    #[DataProvider('unaLetra')]
    public function test_una_letra_no_es_busqueda(string $letra): void
    {
        $this->reparacion('Ana Torres', '2026-11-10 10:00:00');
        $this->reparacion('Pedro Ruiz', '2026-11-11 10:00:00');

        $html = $this->pagina(['estado' => 'todos', 'periodo' => 'mes', 'buscar' => $letra]);

        $this->assertCount(2, $this->idsEnLaLista($html));
        $this->assertSame('Mostrando todas las reparaciones de este mes', $this->indicador($html));

        // El campo conserva la letra, y la × está para borrarla
        $this->assertStringContainsString('id="input-buscar" value="' . $letra . '"', $html);
        $this->assertStringContainsString('aria-label="Limpiar búsqueda"', $html);
    }

    public function test_dos_letras_si_buscan(): void
    {
        $ana = $this->reparacion('Ana Torres', '2026-11-10 10:00:00');
        $this->reparacion('Pedro Ruiz', '2026-11-11 10:00:00');

        $html = $this->pagina(['estado' => 'todos', 'periodo' => 'mes', 'buscar' => 'an']);

        $this->assertSame([$ana->id], $this->idsEnLaLista($html));
        $this->assertSame('1 resultado para "an" entre todas las reparaciones de este mes', $this->indicador($html));
    }
}
