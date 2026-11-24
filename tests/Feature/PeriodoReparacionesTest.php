<?php

namespace Tests\Feature;

use App\Models\Reparacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * Filtro de periodo de Reparaciones y del Dashboard (App\Support\FiltroPeriodo).
 *
 * Todos cuentan por created_at, con la hora de la app (Bogotá). «Últimos 3 meses» y
 * «Últimos 6 meses» van desde el mismo día de hace 3 o 6 meses, a las 00:00, hasta hoy
 * incluido. Si ese día no existe (31 de mayo menos 3 meses), desde el último día de ese mes.
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
            'personalizado sin fechas: el mes actual' => ['personalizado', '2026-11-01 00:00:00', '2026-10-31 23:59:59'],
            'periodo desconocido: el mes actual'      => ['otro',          '2026-11-01 00:00:00', '2026-10-31 23:59:59'],
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

    /** El Dashboard filtra con el mismo cálculo (también recibe la corrección de fin de mes). */
    public function test_el_dashboard_cuenta_igual_y_no_ofrece_6_meses(): void
    {
        $this->reparacionDel('2026-02-28 00:00:00', ['estado' => 'entregado']);
        $this->reparacionDel('2026-02-27 23:59:59', ['estado' => 'entregado']);

        $this->travelTo(Carbon::parse('2026-05-31 10:00:00'));

        // La gráfica de ingresos del año usa MONTH(), que MySQL tiene y SQLite no: se agrega
        // aquí, solo para poder abrir el Dashboard en la prueba (createFunction desde PHP 8.4)
        $pdo = DB::connection()->getPdo();
        $mes = fn (?string $fecha) => $fecha === null ? null : (int) substr($fecha, 5, 2);
        $pdo instanceof \Pdo\Sqlite ? $pdo->createFunction('MONTH', $mes, 1) : $pdo->sqliteCreateFunction('MONTH', $mes, 1);

        $respuesta = $this->get(route('admin.dashboard', ['periodo' => 'trimestre']))->assertOk();

        $respuesta->assertViewHas('metricas', fn ($metricas) => (int) $metricas->completadas === 1);
        $respuesta->assertViewHas('contadores', fn ($contadores) => $contadores['entregado'] === 1);

        // «Últimos 6 meses» va solo en Reparaciones
        $this->assertStringNotContainsString('value="semestre"', $respuesta->getContent());
    }

    public function test_en_proceso_no_filtra_por_fecha(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 15:40:00'));

        $deHaceUnAnio = $this->reparacionDel('2025-11-01 09:00:00');

        $this->assertSame([$deHaceUnAnio->id], $this->idsDelPeriodo('semestre', ['estado' => 'en_proceso']));
    }
}
