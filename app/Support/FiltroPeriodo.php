<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Filtro de periodo de las reparaciones, por fecha de ingreso (created_at): el select de
 * Reparaciones y el del Dashboard. Es el único lugar que decide qué fechas entran en cada
 * periodo (antes estaba copiado en los dos controladores). Todo cuenta con la hora de la
 * app (America/Bogota, config/app.php).
 *
 *   hoy, semana, mes,      el día, la semana (de lunes a domingo), el mes, el mes anterior o
 *   mes_pasado, anio       el año, completos
 *   trimestre, semestre    «Últimos 3 meses» y «Últimos 6 meses»: desde el mismo día de hace 3
 *                          o 6 meses, a las 00:00, hasta hoy incluido (inicioUltimosMeses)
 *   personalizado          de "desde" a "hasta", los dos días completos. Con una sola fecha el
 *                          rango queda abierto: desde ese día, o todo hasta ese día
 *
 * Lo que llega en la URL se revisa primero con delPedido(), y la consulta, el select, los
 * campos de fecha y el texto de la lista usan eso mismo: así nunca dicen cosas distintas.
 */
final class FiltroPeriodo
{
    /** Los periodos que entiende aplicar(). Cada página ofrece estos o algunos (Dashboard). */
    public const PERIODOS = ['hoy', 'semana', 'mes', 'mes_pasado', 'trimestre', 'semestre', 'anio', 'personalizado'];

    /**
     * Periodo y fechas del pedido, ya revisados:
     *   - periodo: uno de $permitidos. Si no (vacío, una lista, desconocido, o uno que esa
     *     página no ofrece): «mes».
     *   - desde / hasta: solo fechas YYYY-MM-DD que existen. Si no: null. Al revés (desde
     *     después de hasta), se invierten.
     *   - «personalizado» sin ninguna fecha que sirva: «mes».
     *
     * @return array{periodo: string, desde: ?string, hasta: ?string}
     */
    public static function delPedido(Request $request, array $permitidos = self::PERIODOS): array
    {
        $periodo = $request->input('periodo');
        $periodo = is_string($periodo) && in_array($periodo, $permitidos, true) ? $periodo : 'mes';

        $desde = self::fecha($request->input('desde'));
        $hasta = self::fecha($request->input('hasta'));

        if ($desde && $hasta && $desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        if ($periodo === 'personalizado' && ! $desde && ! $hasta) {
            $periodo = 'mes';
        }

        return ['periodo' => $periodo, 'desde' => $desde, 'hasta' => $hasta];
    }

    /**
     * Filtra la consulta por el periodo. Sirve para Eloquent y para DB::table(): el
     * Dashboard usa las dos (métricas y gráfica por estado). Recibe lo que devolvió delPedido().
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  string|null  $desde  YYYY-MM-DD, solo con «personalizado»
     * @param  string|null  $hasta  YYYY-MM-DD, solo con «personalizado»
     */
    public static function aplicar($query, string $periodo, ?string $desde = null, ?string $hasta = null): void
    {
        if ($periodo === 'personalizado' && ! $desde && ! $hasta) {
            $periodo = 'mes';
        }

        switch ($periodo) {
            case 'hoy':
                $query->whereDate('created_at', today());
                break;

            case 'semana':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;

            case 'mes_pasado':
                $mesAnterior = now()->subMonthNoOverflow();
                $query->whereMonth('created_at', $mesAnterior->month)
                    ->whereYear('created_at', $mesAnterior->year);
                break;

            case 'trimestre':
                $query->whereBetween('created_at', [self::inicioUltimosMeses(3), now()->endOfDay()]);
                break;

            case 'semestre':
                $query->whereBetween('created_at', [self::inicioUltimosMeses(6), now()->endOfDay()]);
                break;

            case 'anio':
                $query->whereYear('created_at', now()->year);
                break;

            case 'personalizado':
                // Los dos días entran completos. Con una sola fecha, el otro lado queda abierto
                if ($desde) {
                    $query->where('created_at', '>=', $desde . ' 00:00:00');
                }
                if ($hasta) {
                    $query->where('created_at', '<=', $hasta . ' 23:59:59');
                }
                break;

            default: // 'mes', el periodo por defecto
                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
        }
    }

    /**
     * Cómo lo dice la lista de Reparaciones: «de este mes», «de los últimos 6 meses (del
     * 23/05/2026 a hoy)», «del 10/03/2026 al 12/03/2026», «desde el 10/03/2026»...
     */
    public static function descripcion(string $periodo, ?string $desde = null, ?string $hasta = null): string
    {
        $dia       = fn (string $fecha) => Carbon::createFromFormat('Y-m-d', $fecha)->format('d/m/Y');
        $desdeHace = fn (int $meses) => '(del ' . self::inicioUltimosMeses($meses)->format('d/m/Y') . ' a hoy)';

        return match ($periodo) {
            'hoy'           => 'de hoy',
            'semana'        => 'de esta semana',
            'mes_pasado'    => 'del mes pasado',
            'trimestre'     => 'de los últimos 3 meses ' . $desdeHace(3),
            'semestre'      => 'de los últimos 6 meses ' . $desdeHace(6),
            'anio'          => 'de este año',
            'personalizado' => match (true) {
                $desde && $hasta => 'del ' . $dia($desde) . ' al ' . $dia($hasta),
                (bool) $desde    => 'desde el ' . $dia($desde),
                (bool) $hasta    => 'hasta el ' . $dia($hasta),
                default          => 'de este mes',
            },
            default         => 'de este mes',
        };
    }

    /**
     * Desde cuándo cuentan «Últimos N meses»: el mismo día de hace N meses, a las 00:00. Si ese
     * día no existe, el último de ese mes: el 31 de mayo, 3 meses atrás es el 28 de febrero. Con
     * subMonths() sería el 3 de marzo y se perderían 3 días.
     */
    public static function inicioUltimosMeses(int $meses): CarbonInterface
    {
        return today()->subMonthsNoOverflow($meses);
    }

    /** La fecha si es YYYY-MM-DD y existe (2026-02-30 no); si no, null. */
    private static function fecha(mixed $valor): ?string
    {
        if (! is_string($valor) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $partes)) {
            return null;
        }

        return checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]) ? $valor : null;
    }
}
