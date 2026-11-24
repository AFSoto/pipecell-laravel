<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DashboardController;
use App\Models\Reparacion;
use App\Models\User;
use App\Support\FiltroPeriodo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * Filtro de periodo de Reparaciones y del Dashboard (App\Support\FiltroPeriodo).
 *
 * Todos cuentan por created_at, con la hora de la app (Bogotá). «Últimos 3 meses» y
 * «Últimos 6 meses» van desde el mismo día de hace 3 o 6 meses, a las 00:00, hasta hoy
 * incluido. Si ese día no existe (31 de mayo menos 3 meses), desde el último día de ese mes.
 *
 * Lo que llega en la URL se revisa antes de usarlo (FiltroPeriodo::delPedido): un periodo
 * vacío o desconocido es el predefinido de la página (Reparaciones: «Últimos 6 meses»;
 * Dashboard: «Este mes»), una fecha que no existe cuenta como vacía, las fechas al revés se
 * invierten y con una sola fecha el rango queda abierto. El select, los campos de fecha y el
 * indicador muestran lo que de verdad se filtró.
 */
class PeriodoReparacionesTest extends TestCase
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

    /** Una reparación registrada en esa fecha y hora ('Y-m-d H:i:s', hora de Bogotá). */
    private function reparacionDel(string $fecha, array $datos = []): Reparacion
    {
        $reparacion = $this->crearReparacion($this->usuario);

        Reparacion::whereKey($reparacion->id)->update(['created_at' => $fecha] + $datos);

        return $reparacion;
    }

    /** Ids de la lista de Reparaciones con ese periodo (si $extra no dice otro estado, todos). */
    private function idsDelPeriodo(string $periodo, array $extra = []): array
    {
        $respuesta = $this->get(route('admin.reparaciones.index', $extra + ['estado' => 'todos', 'periodo' => $periodo]));

        return $this->idsEnLaLista($respuesta->assertOk()->getContent());
    }

    /** La opción marcada en un select de la página (null si ninguna: el navegador muestra la primera). */
    private function marcada(string $html, string $select): ?string
    {
        preg_match('#<select name="' . $select . '".*?</select>#s', $html, $bloque);
        preg_match('#<option value="([a-z_]+)"\s+selected#', $bloque[0] ?? '', $opcion);

        return $opcion[1] ?? null;
    }

    /** Los valores de las opciones del select de periodo. */
    private function opcionesDePeriodo(string $html): array
    {
        preg_match('#<select name="periodo".*?</select>#s', $html, $bloque);
        preg_match_all('#<option value="([a-z_]+)"#', $bloque[0] ?? '', $valores);

        return $valores[1];
    }

    /** El texto del indicador de Reparaciones, sin etiquetas ni espacios de más. */
    private function indicador(string $html): string
    {
        preg_match('#data-en-vivo="indicador"[^>]*>(.*?)</p>#s', $html, $texto);

        return trim(preg_replace('/\s+/', ' ', strip_tags($texto[1] ?? '')));
    }

    /**
     * [periodo, la que sale, la que no sale, parámetros extra], con hoy = miércoles 25 nov 2026.
     * Las fechas de «semana» sirven si la semana empieza el lunes (23) o el domingo (22).
     */
    public static function bordesDeCadaPeriodo(): array
    {
        $marzo = ['desde' => '2026-03-10', 'hasta' => '2026-03-12'];

        return [
            'hoy'                          => ['hoy',           '2026-11-25 00:00:00', '2026-11-24 23:59:59'],
            'esta semana'                  => ['semana',        '2026-11-23 00:00:00', '2026-11-21 23:59:59'],
            'este mes'                     => ['mes',           '2026-11-01 00:00:00', '2026-10-31 23:59:59'],
            'mes pasado, último segundo'   => ['mes_pasado',    '2026-10-31 23:59:59', '2026-11-01 00:00:00'],
            'mes pasado, primer segundo'   => ['mes_pasado',    '2026-10-01 00:00:00', '2026-09-30 23:59:59'],
            'últimos 3 meses'              => ['trimestre',     '2026-08-25 00:00:00', '2026-08-24 23:59:59'],
            'últimos 6 meses'              => ['semestre',      '2026-05-25 00:00:00', '2026-05-24 23:59:59'],
            'este año'                     => ['anio',          '2026-01-01 00:00:00', '2025-12-31 23:59:59'],
            'personalizado, último día'    => ['personalizado', '2026-03-12 23:59:59', '2026-03-13 00:00:00', $marzo],
            'personalizado, primer día'    => ['personalizado', '2026-03-10 00:00:00', '2026-03-09 23:59:59', $marzo],
            'personalizado al revés: se invierten' => ['personalizado', '2026-03-10 00:00:00', '2026-03-13 00:00:00', ['desde' => '2026-03-12', 'hasta' => '2026-03-10']],
            'personalizado solo desde: hasta hoy'  => ['personalizado', '2026-06-15 10:00:00', '2026-03-09 23:59:59', ['desde' => '2026-03-10']],
            'personalizado solo hasta: todo antes' => ['personalizado', '2025-01-15 10:00:00', '2026-03-13 00:00:00', ['hasta' => '2026-03-12']],
            'personalizado con un día que no existe: cuenta como vacío' => ['personalizado', '2025-01-15 10:00:00', '2026-03-13 00:00:00', ['desde' => '2026-02-30', 'hasta' => '2026-03-12']],
            'personalizado sin fechas: el predefinido, 6 meses' => ['personalizado', '2026-05-25 00:00:00', '2026-05-24 23:59:59'],
            'periodo desconocido: el predefinido, 6 meses'      => ['otro',          '2026-05-25 00:00:00', '2026-05-24 23:59:59'],
        ];
    }

    #[DataProvider('bordesDeCadaPeriodo')]
    public function test_cada_periodo_va_justo_hasta_sus_bordes(string $periodo, string $sale, string $noSale, array $extra = []): void
    {
        $this->travelTo(Carbon::parse('2026-11-25 15:40:00'));

        $dentro = $this->reparacionDel($sale);
        $this->reparacionDel($noSale);

        $this->assertSame([$dentro->id], $this->idsDelPeriodo($periodo, $extra));
    }

    public function test_los_ultimos_3_y_6_meses_cuentan_hoy(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 15:40:00'));

        $deHoy = $this->reparacionDel('2026-11-23 15:39:00');

        $this->assertSame([$deHoy->id], $this->idsDelPeriodo('trimestre'));
        $this->assertSame([$deHoy->id], $this->idsDelPeriodo('semestre'));
    }

    /**
     * Si el día de hace 3 o 6 meses no existe, se cuenta desde el último día de ese mes. Antes
     * (subMonths) se pasaba al mes siguiente: el 31 de mayo, «Últimos 3 meses» empezaba el 3 de marzo.
     */
    public function test_a_fin_de_mes_no_se_pierden_dias(): void
    {
        $febrero28  = $this->reparacionDel('2026-02-28 00:00:00');
        $this->reparacionDel('2026-02-27 23:59:59');
        $bisiesto29 = $this->reparacionDel('2028-02-29 00:00:00');
        $this->reparacionDel('2028-02-28 23:59:59');

        // 31 de mayo menos 3 meses: 28 de febrero (antes, 3 de marzo)
        $this->travelTo(Carbon::parse('2026-05-31 10:00:00'));
        $this->assertSame([$febrero28->id], $this->idsDelPeriodo('trimestre'));

        // 31 de agosto menos 6 meses: 28 de febrero
        $this->travelTo(Carbon::parse('2026-08-31 10:00:00'));
        $this->assertSame([$febrero28->id], $this->idsDelPeriodo('semestre'));

        // En año bisiesto: 29 de febrero
        $this->travelTo(Carbon::parse('2028-08-31 10:00:00'));
        $this->assertSame([$bisiesto29->id], $this->idsDelPeriodo('semestre'));
    }

    public function test_el_filtro_ofrece_ultimos_6_meses_y_dice_desde_cuando_cuenta(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 15:40:00'));

        $html = $this->get(route('admin.reparaciones.index', ['estado' => 'todos', 'periodo' => 'semestre']))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('#<option value="semestre"\s+selected>Últimos 6 meses</option>#u', $html);
        $this->assertStringContainsString('Mostrando todas las reparaciones de los últimos 6 meses (del 23/05/2026 a hoy)', $html);

        // En el select va entre «Últimos 3 meses» y «Este año»
        $this->assertMatchesRegularExpression('#value="trimestre".*?value="semestre".*?value="anio"#s', $html);

        $html = $this->get(route('admin.reparaciones.index', ['estado' => 'entregado', 'periodo' => 'trimestre']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Mostrando reparaciones entregadas de los últimos 3 meses (del 23/08/2026 a hoy)', $html);
    }

    /** «Personalizado»: el indicador y los campos de fecha dicen el rango que de verdad se usó. */
    public function test_personalizado_dice_el_rango_que_se_uso(): void
    {
        $this->travelTo(Carbon::parse('2026-11-25 15:40:00'));
        $pagina = fn (array $fechas) => $this->get(route('admin.reparaciones.index', ['estado' => 'todos', 'periodo' => 'personalizado'] + $fechas))
            ->assertOk()
            ->getContent();

        // Al revés: se invierten, también en los campos
        $html = $pagina(['desde' => '2026-03-12', 'hasta' => '2026-03-10']);
        $this->assertSame('Mostrando todas las reparaciones del 10/03/2026 al 12/03/2026', $this->indicador($html));
        $this->assertStringContainsString('name="desde" value="2026-03-10"', $html);
        $this->assertStringContainsString('name="hasta" value="2026-03-12"', $html);

        // Una sola fecha: el rango queda abierto
        $this->assertSame('Mostrando todas las reparaciones desde el 10/03/2026', $this->indicador($pagina(['desde' => '2026-03-10'])));
        $this->assertSame('Mostrando todas las reparaciones hasta el 12/03/2026', $this->indicador($pagina(['hasta' => '2026-03-12'])));

        // Sin ninguna fecha que sirva: el periodo predefinido (Últimos 6 meses), y el select lo dice
        $html = $pagina(['desde' => 'abc', 'hasta' => '2026-02-30']);
        $this->assertSame('semestre', $this->marcada($html, 'periodo'));
        $this->assertSame('Mostrando todas las reparaciones de los últimos 6 meses (del 25/05/2026 a hoy)', $this->indicador($html));
    }

    /**
     * Sin periodo en la URL (como al pasar de «En proceso» a «Todos»), Reparaciones arranca en
     * «Últimos 6 meses»: es el periodo predefinido.
     */
    public function test_sin_periodo_en_la_url_arranca_en_los_ultimos_6_meses(): void
    {
        $this->travelTo(Carbon::parse('2026-11-25 15:40:00'));

        $dentro = $this->reparacionDel('2026-05-25 00:00:00');
        $this->reparacionDel('2026-05-24 23:59:59');

        $html = $this->get(route('admin.reparaciones.index', ['estado' => 'todos']))->assertOk()->getContent();

        $this->assertSame('semestre', $this->marcada($html, 'periodo'));
        $this->assertSame('Mostrando todas las reparaciones de los últimos 6 meses (del 25/05/2026 a hoy)', $this->indicador($html));
        $this->assertSame([$dentro->id], $this->idsEnLaLista($html));
    }

    /** «Limpiar filtros» no sale solo por estar en el periodo predefinido; con otro periodo, sí. */
    public function test_limpiar_filtros_no_sale_por_el_periodo_predefinido(): void
    {
        $this->get(route('admin.reparaciones.index', ['estado' => 'todos']))
            ->assertOk()
            ->assertDontSee('Limpiar filtros');

        $this->get(route('admin.reparaciones.index', ['estado' => 'todos', 'periodo' => 'semestre']))
            ->assertOk()
            ->assertDontSee('Limpiar filtros');

        $this->get(route('admin.reparaciones.index', ['estado' => 'todos', 'periodo' => 'mes']))
            ->assertOk()
            ->assertSee('Limpiar filtros');
    }

    /**
     * [query string, estado marcado, periodo marcado, indicador], con hoy = 25 nov 2026. Ninguna
     * da error, y el select marca lo que de verdad se filtró (antes, sin nada marcado, se veía
     * «Hoy»). Lo que no sirve queda en el predefinido: «Últimos 6 meses».
     */
    public static function urlsRarasDeReparaciones(): array
    {
        $seisMeses = 'Mostrando todas las reparaciones de los últimos 6 meses (del 25/05/2026 a hoy)';

        return [
            'periodo vacío'          => ['estado=todos&periodo=',                                     'todos',      'semestre', $seisMeses],
            'periodo como lista'     => ['estado=todos&periodo[]=semestre',                           'todos',      'semestre', $seisMeses],
            'periodo desconocido'    => ['estado=todos&periodo=xyz',                                  'todos',      'semestre', $seisMeses],
            'periodo en mayúsculas'  => ['estado=todos&periodo=SEMESTRE',                             'todos',      'semestre', $seisMeses],
            'fechas que no son fechas' => ['estado=todos&periodo=personalizado&desde=abc&hasta=def',  'todos',      'semestre', $seisMeses],
            'fechas como lista'      => ['estado=todos&periodo=personalizado&desde[]=x&hasta[]=y',    'todos',      'semestre', $seisMeses],
            'búsqueda como lista'    => ['estado=todos&periodo=mes&buscar[]=x',                       'todos',      'mes', 'Mostrando todas las reparaciones de este mes'],
            'estado desconocido'     => ['estado=xyz&periodo=semestre',                               'en_proceso', 'semestre', 'Mostrando reparaciones en proceso'],
            'estado como lista'      => ['estado[]=todos',                                            'en_proceso', 'semestre', 'Mostrando reparaciones en proceso'],
        ];
    }

    #[DataProvider('urlsRarasDeReparaciones')]
    public function test_una_url_rara_en_reparaciones_no_da_error(string $query, string $estado, string $periodo, string $indicador): void
    {
        $this->travelTo(Carbon::parse('2026-11-25 15:40:00'));

        $html = $this->get('/admin/reparaciones?' . $query)->assertOk()->getContent();

        $this->assertSame($estado, $this->marcada($html, 'estado'));
        $this->assertSame($periodo, $this->marcada($html, 'periodo'));
        $this->assertSame($indicador, $this->indicador($html));
    }

    /** [query string]: en el Dashboard todas terminan en «Este mes», también «Últimos 6 meses», que no ofrece. */
    public static function urlsRarasDelDashboard(): array
    {
        return [
            'periodo vacío'       => ['periodo='],
            'periodo como lista'  => ['periodo[]=x'],
            'periodo desconocido' => ['periodo=xyz'],
            'últimos 6 meses (solo está en Reparaciones)' => ['periodo=semestre'],
            'fechas que no son fechas' => ['periodo=personalizado&desde=abc&hasta[]=y'],
        ];
    }

    #[DataProvider('urlsRarasDelDashboard')]
    public function test_una_url_rara_en_el_dashboard_no_da_error(string $query): void
    {
        $respuesta = $this->get('/admin/dashboard?' . $query)->assertOk();

        $respuesta->assertViewHas('periodo', 'mes');
        $this->assertSame('mes', $this->marcada($respuesta->getContent(), 'periodo'));
    }

    /** Si alguien agrega una opción al select sin que el filtro la acepte (o al revés), esto falla. */
    public function test_cada_select_ofrece_lo_que_su_controlador_acepta(): void
    {
        $reparaciones = $this->opcionesDePeriodo($this->get(route('admin.reparaciones.index', ['estado' => 'todos']))->getContent());
        $dashboard    = $this->opcionesDePeriodo($this->get(route('admin.dashboard'))->assertOk()->getContent());

        $this->assertEqualsCanonicalizing(FiltroPeriodo::PERIODOS, $reparaciones);
        $this->assertEqualsCanonicalizing(DashboardController::PERIODOS, $dashboard);

        // «Últimos 6 meses» va solo en Reparaciones
        $this->assertNotContains('semestre', DashboardController::PERIODOS);
    }

    /** El Dashboard filtra con el mismo cálculo (también recibe la corrección de fin de mes). */
    public function test_el_dashboard_cuenta_igual_y_no_ofrece_6_meses(): void
    {
        $this->reparacionDel('2026-02-28 00:00:00', ['estado' => 'entregado']);
        $this->reparacionDel('2026-02-27 23:59:59', ['estado' => 'entregado']);

        $this->travelTo(Carbon::parse('2026-05-31 10:00:00'));

        // Sin registrar MONTH() aquí: tests/TestCase.php lo agrega a SQLite en todos los tests
        $respuesta = $this->get(route('admin.dashboard', ['periodo' => 'trimestre']))->assertOk();

        $respuesta->assertViewHas('metricas', fn ($metricas) => (int) $metricas->completadas === 1);
        $respuesta->assertViewHas('contadores', fn ($contadores) => $contadores['entregado'] === 1);

        // «Últimos 6 meses» va solo en Reparaciones
        $this->assertStringNotContainsString('value="semestre"', $respuesta->getContent());
    }

    /** Con «Últimos 6 meses» en la URL, el Dashboard (que no lo ofrece) muestra este mes. */
    public function test_el_dashboard_con_6_meses_muestra_este_mes(): void
    {
        $this->travelTo(Carbon::parse('2026-11-25 15:40:00'));

        $this->reparacionDel('2026-11-02 10:00:00', ['estado' => 'entregado']);   // este mes
        $this->reparacionDel('2026-08-10 10:00:00', ['estado' => 'entregado']);   // en 6 meses, no en este mes

        $this->get(route('admin.dashboard', ['periodo' => 'semestre']))
            ->assertOk()
            ->assertViewHas('periodo', 'mes')
            ->assertViewHas('metricas', fn ($metricas) => (int) $metricas->completadas === 1);
    }

    public function test_en_proceso_no_filtra_por_fecha(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 15:40:00'));

        $deHaceUnAnio = $this->reparacionDel('2025-11-01 09:00:00');

        $this->assertSame([$deHaceUnAnio->id], $this->idsDelPeriodo('semestre', ['estado' => 'en_proceso']));
    }
}
