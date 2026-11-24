<?php

namespace App\Support;

use Carbon\CarbonInterface;

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
 *   personalizado          de "desde" a "hasta", los dos días completos
 *
 * Cualquier otro valor, o «personalizado» sin las dos fechas: el mes actual.
 */
final class FiltroPeriodo
{
    /**
     * Filtra la consulta por el periodo. Sirve para Eloquent y para DB::table(): el
     * Dashboard usa las dos (métricas y gráfica por estado).
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  string|null  $desde  YYYY-MM-DD, solo con «personalizado»
     * @param  string|null  $hasta  YYYY-MM-DD, solo con «personalizado»
     */
    public static function aplicar($query, string $periodo, ?string $desde = null, ?string $hasta = null): void
    {
        if ($periodo === 'personalizado' && ! ($desde && $hasta)) {
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
                // ' 23:59:59': el último día entra completo
                $query->whereBetween('created_at', [$desde, $hasta . ' 23:59:59']);
                break;

            default: // 'mes', el periodo por defecto
                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
        }
    }

    /**
     * Desde cuándo cuentan «Últimos N meses»: el mismo día de hace N meses, a las 00:00. Si ese
     * día no existe, el último de ese mes: el 31 de mayo, 3 meses atrás es el 28 de febrero. Con
     * subMonths() sería el 3 de marzo y se perderían 3 días.
     *
     * La lista de Reparaciones también la usa para mostrar desde qué fecha cuenta.
     */
    public static function inicioUltimosMeses(int $meses): CarbonInterface
    {
        return today()->subMonthsNoOverflow($meses);
    }
}
