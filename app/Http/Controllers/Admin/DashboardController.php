<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Caja;
use App\Models\Reparacion;
use App\Enums\EstadoReparacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador del Dashboard principal de PipeCell.
 *
 * Centraliza todos los cálculos de métricas del negocio usando
 * agregaciones SQL (SUM, COUNT, GROUP BY) para minimizar la cantidad
 * de consultas y evitar traer registros innecesarios a PHP.
 *
 * Datos que calcula:
 *  - Ingresos y ganancia del periodo seleccionado
 *  - Porcentaje de cambio vs periodo anterior (solo mes/año)
 *  - Conteo de reparaciones activas, por estado y completadas
 *  - Ingresos mensuales de los últimos 12 meses (para gráfica)
 *  - Estado de todas las cajas con cliente asociado si está ocupada
 *  - Últimas 5 reparaciones con sus relaciones
 *  - Top 5 marcas más reparadas
 */
class DashboardController extends Controller
{
    /**
     * Muestra el dashboard con todos los datos del negocio.
     *
     * Parámetros GET aceptados:
     *   - periodo:  hoy | semana | mes | mes_pasado | trimestre | anio | personalizado
     *   - desde:    fecha (YYYY-MM-DD) — solo cuando periodo = personalizado
     *   - hasta:    fecha (YYYY-MM-DD) — solo cuando periodo = personalizado
     */
    public function index(Request $request)
    {
        // Leer parámetros del filtro — 'mes' es el valor por defecto
        $periodo = $request->input('periodo', 'mes');
        $desde   = $request->input('desde');
        $hasta   = $request->input('hasta');

        // ── 1. MÉTRICAS DEL PERIODO SELECCIONADO ──
        // Una sola consulta SQL con SUM/COUNT para ingresos, ganancia y completadas
        $metricas = $this->obtenerMetricasPeriodo($periodo, $desde, $hasta);

        // Porcentaje de cambio vs periodo anterior (solo disponible para mes y anio)
        $cambioPorcentaje = $this->calcularCambioPorcentaje($periodo, $metricas);

        // ── 2. REPARACIONES ACTIVAS ──
        // Sin filtro de fecha — siempre refleja el estado actual del taller
        // Se cuentan tanto las "en proceso" como las "arregladas" (listas pero no entregadas)
        $reparacionesActivas = Reparacion::whereIn('estado', [
            EstadoReparacion::EnProceso,
            EstadoReparacion::Arreglado,
        ])->count();

        // ── 3. CONTADORES POR ESTADO (para la gráfica doughnut) ──
        // Aplicamos el mismo filtro de periodo que las tarjetas KPI, así el doughnut
        // muestra la distribución de reparaciones DENTRO del periodo seleccionado.
        // Usamos DB::table() para que las claves del pluck sean strings crudos
        // y no instancias del enum (que causaría "Illegal offset type").
        $queryContadores = DB::table('reparaciones')
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado');

        $this->aplicarFiltroPeriodo($queryContadores, $periodo, $desde, $hasta);

        $contadoresBD = $queryContadores->pluck('total', 'estado');

        $contadores = [
            'en_proceso' => (int) ($contadoresBD['en_proceso'] ?? 0),
            'arreglado'  => (int) ($contadoresBD['arreglado'] ?? 0),
            'entregado'  => (int) ($contadoresBD['entregado'] ?? 0),
        ];

        // ── 4. INGRESOS PARA LA GRÁFICA DE BARRAS (granularidad según periodo) ──
        $ingresosPorMes = $this->obtenerIngresosPorMes($periodo, $desde, $hasta);

        // ── 5. ESTADO DE CAJAS AGRUPADAS POR GRUPO ──
        // Incluye el nombre del cliente si la caja está ocupada
        $cajasPorGrupo = $this->obtenerEstadoCajas();

        // ── 6. ÚLTIMAS 5 REPARACIONES ──
        // Eager loading de caja y abonos para evitar N+1 en la vista
        $ultimasReparaciones = Reparacion::with(['caja', 'abonos'])
            ->latest()
            ->take(5)
            ->get();

        // ── 7. TOP 5 MARCAS MÁS REPARADAS ──
        // Agregación con GROUP BY — eficiente aunque haya miles de registros
        $topMarcas = Reparacion::select('marca', DB::raw('COUNT(*) as total'))
            ->whereNotNull('marca')
            ->where('marca', '!=', '')
            ->groupBy('marca')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        // Arrays separados de labels/datos listos para Chart.js (horizontal bar)
        $topMarcasChart = [
            'labels' => $topMarcas->pluck('marca')->toArray(),
            'datos'  => $topMarcas->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
        ];

        // ── 8. ACTIVIDAD DIARIA (últimos 30 días para la gráfica de área) ──
        $reparacionesDiarias = $this->obtenerReparacionesDiarias();

        // ── 9. ESTADÍSTICAS DE COBRO (reparaciones activas en taller) ──
        $estadisticasCobro = $this->obtenerEstadisticasCobro();

        return view('admin.dashboard', compact(
            'periodo', 'desde', 'hasta',
            'metricas', 'cambioPorcentaje',
            'reparacionesActivas', 'contadores',
            'ingresosPorMes', 'cajasPorGrupo',
            'ultimasReparaciones',
            'topMarcas', 'topMarcasChart',
            'reparacionesDiarias', 'estadisticasCobro'
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // MÉTODOS PRIVADOS DE CÁLCULO
    // ─────────────────────────────────────────────────────────────

    /**
     * Aplica el filtro de periodo a un query builder de Eloquent.
     *
     * El query se modifica directamente (los objetos en PHP se pasan
     * por referencia implícitamente), por eso no retorna nada.
     *
     * Este método es la fuente de verdad para el filtro de periodo —
     * replicar esta lógica en otro lado crea inconsistencias.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    private function aplicarFiltroPeriodo($query, string $periodo, ?string $desde, ?string $hasta): void
    {
        switch ($periodo) {

            case 'hoy':
                // Solo registros del día de hoy (ignora la hora)
                $query->whereDate('created_at', today());
                break;

            case 'semana':
                // Semana actual de lunes a domingo según la zona horaria de la app
                $query->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ]);
                break;

            case 'mes_pasado':
                // Mes anterior completo (ej: si estamos en marzo, filtra febrero)
                $query->whereMonth('created_at', now()->subMonth()->month)
                      ->whereYear('created_at', now()->subMonth()->year);
                break;

            case 'trimestre':
                // Últimos 90 días (aproximado — 3 meses hacia atrás desde hoy)
                $query->whereBetween('created_at', [
                    now()->subMonths(3)->startOfDay(),
                    now()->endOfDay(),
                ]);
                break;

            case 'anio':
                // Año en curso completo (enero 1 hasta hoy)
                $query->whereYear('created_at', now()->year);
                break;

            case 'personalizado':
                // Rango personalizado — si faltan fechas, cae al mes actual
                if ($desde && $hasta) {
                    // Se agrega ' 23:59:59' al $hasta para incluir todo el día final
                    $query->whereBetween('created_at', [$desde, $hasta . ' 23:59:59']);
                } else {
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                }
                break;

            default: // 'mes' — default de la aplicación
                // Mes en curso
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
        }
    }

    /**
     * Obtiene ingresos totales, ganancia neta y conteo de reparaciones
     * entregadas del periodo seleccionado — todo en UNA sola consulta SQL.
     *
     * Usar COALESCE evita que SUM retorne NULL cuando no hay registros,
     * lo que causaría errores en el formateo numérico de la vista.
     */
    private function obtenerMetricasPeriodo(string $periodo, ?string $desde, ?string $hasta): object
    {
        $query = Reparacion::entregadas()
            ->selectRaw('
                COALESCE(SUM(valor_total), 0)                                              AS ingresos,
                COALESCE(SUM(valor_total) - SUM(COALESCE(costo_repuestos, 0)), 0)          AS ganancia,
                COUNT(*)                                                                    AS completadas
            ');

        // Aplicamos el mismo filtro de periodo que se usa en reparaciones
        $this->aplicarFiltroPeriodo($query, $periodo, $desde, $hasta);

        // ->first() retorna un stdObject con las propiedades ingresos, ganancia, completadas
        return $query->first();
    }

    /**
     * Calcula el porcentaje de variación de ingresos y ganancia
     * comparando el periodo actual con el anterior.
     *
     * Solo aplica para 'mes' (vs mes pasado) y 'anio' (vs año pasado).
     * Para otros periodos retorna null — la vista no muestra el badge.
     *
     * Fórmula: ((actual - anterior) / anterior) × 100
     *
     * @return array{ingresos: float|null, ganancia: float|null}
     */
    private function calcularCambioPorcentaje(string $periodo, object $metricas): array
    {
        // Solo tiene sentido comparar para estos dos periodos
        if (! in_array($periodo, ['mes', 'anio'])) {
            return ['ingresos' => null, 'ganancia' => null];
        }

        // Construir la consulta del periodo anterior
        $queryAnterior = Reparacion::entregadas()
            ->selectRaw('
                COALESCE(SUM(valor_total), 0)                                     AS ingresos,
                COALESCE(SUM(valor_total) - SUM(COALESCE(costo_repuestos, 0)), 0) AS ganancia
            ');

        if ($periodo === 'mes') {
            // Comparar con el mes pasado
            $queryAnterior->whereMonth('created_at', now()->subMonth()->month)
                          ->whereYear('created_at', now()->subMonth()->year);
        } else {
            // Comparar con el año pasado
            $queryAnterior->whereYear('created_at', now()->subYear()->year);
        }

        $anterior = $queryAnterior->first();

        // Función auxiliar: calcula el % de cambio evitando división por cero
        $calcPct = function (float $actual, float $anterior): ?float {
            if ($anterior == 0) {
                // No podemos calcular % si el periodo anterior fue $0
                return null;
            }
            return round((($actual - $anterior) / $anterior) * 100, 1);
        };

        return [
            'ingresos' => $calcPct((float) $metricas->ingresos, (float) $anterior->ingresos),
            'ganancia' => $calcPct((float) $metricas->ganancia, (float) $anterior->ganancia),
        ];
    }

    /**
     * Obtiene los datos de ingresos para la gráfica de barras.
     * La granularidad (diaria, mensual) y el rango cambian según el periodo:
     *
     *   hoy / semana    → últimos 30 días, agrupado por DÍA
     *   mes_pasado      → últimos 13 meses, agrupado por MES (incluye mes anterior completo)
     *   trimestre       → últimos 6 meses, agrupado por MES
     *   anio            → enero a diciembre del año actual, agrupado por MES
     *   personalizado   → últimos 12 meses, agrupado por MES
     *   mes (default)   → últimos 12 meses, agrupado por MES
     *
     * Retorna:
     *   labels   → etiquetas del eje X
     *   datos    → valores de ingresos paralelos a labels
     *   titulo   → título dinámico para la cabecera del card
     *   subtitulo → subtítulo dinámico
     */
    private function obtenerIngresosPorMes(string $periodo, ?string $desde, ?string $hasta): array
    {
        $nombresMeses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        // ── Caso 1: hoy / semana → granularidad DIARIA (últimos 30 días) ──────
        if (in_array($periodo, ['hoy', 'semana'])) {
            $resultados = Reparacion::entregadas()
                ->where('created_at', '>=', now()->subDays(29)->startOfDay())
                ->selectRaw('DATE(created_at) AS fecha, SUM(valor_total) AS total')
                ->groupBy('fecha')
                ->orderBy('fecha')
                ->get()
                ->keyBy('fecha');

            $labels = [];
            $datos  = [];
            for ($i = 29; $i >= 0; $i--) {
                $fecha    = now()->subDays($i);
                $fechaKey = $fecha->format('Y-m-d');
                $labels[] = $fecha->day . ' ' . $nombresMeses[$fecha->month - 1];
                $datos[]  = isset($resultados[$fechaKey]) ? (float) $resultados[$fechaKey]->total : 0;
            }

            return [
                'labels'    => $labels,
                'datos'     => $datos,
                'titulo'    => 'Ingresos diarios',
                'subtitulo' => 'Últimos 30 días · reparaciones entregadas',
            ];
        }

        // ── Caso 2: anio → enero a diciembre del año actual ──────────────────
        if ($periodo === 'anio') {
            $resultados = Reparacion::entregadas()
                ->whereYear('created_at', now()->year)
                ->selectRaw('MONTH(created_at) AS mes, SUM(valor_total) AS total')
                ->groupBy('mes')
                ->get()
                ->keyBy('mes');

            $datos = [];
            for ($m = 1; $m <= 12; $m++) {
                $datos[] = isset($resultados[$m]) ? (float) $resultados[$m]->total : 0;
            }

            return [
                'labels'    => $nombresMeses,   // siempre Ene-Dic
                'datos'     => $datos,
                'titulo'    => 'Ingresos mensuales',
                'subtitulo' => now()->year . ' · reparaciones entregadas',
            ];
        }

        // ── Caso 3: trimestre → últimos 6 meses para tener contexto ──────────
        if ($periodo === 'trimestre') {
            $mesesAtras = 5; // 6 barras: mes actual + 5 anteriores
            $resultados = Reparacion::entregadas()
                ->where('created_at', '>=', now()->subMonths($mesesAtras)->startOfMonth())
                ->selectRaw('YEAR(created_at) AS anio, MONTH(created_at) AS mes, SUM(valor_total) AS total')
                ->groupBy('anio', 'mes')
                ->orderBy('anio')
                ->orderBy('mes')
                ->get()
                ->keyBy(fn ($r) => $r->anio . '-' . str_pad((string) $r->mes, 2, '0', STR_PAD_LEFT));

            $labels = [];
            $datos  = [];
            for ($i = $mesesAtras; $i >= 0; $i--) {
                $fecha    = now()->subMonths($i)->startOfMonth();
                $key      = $fecha->format('Y') . '-' . str_pad((string) $fecha->month, 2, '0', STR_PAD_LEFT);
                $labels[] = $nombresMeses[$fecha->month - 1];
                $datos[]  = isset($resultados[$key]) ? (float) $resultados[$key]->total : 0;
            }

            return [
                'labels'    => $labels,
                'datos'     => $datos,
                'titulo'    => 'Ingresos mensuales',
                'subtitulo' => 'Últimos 6 meses · reparaciones entregadas',
            ];
        }

        // ── Caso 4: personalizado → mensual dentro del rango elegido ────────────
        if ($periodo === 'personalizado' && $desde && $hasta) {
            $fechaInicio = \Carbon\Carbon::parse($desde)->startOfMonth();
            $fechaFin    = \Carbon\Carbon::parse($hasta)->endOfMonth();

            $resultados = Reparacion::entregadas()
                ->whereBetween('created_at', [$desde, $hasta . ' 23:59:59'])
                ->selectRaw('YEAR(created_at) AS anio, MONTH(created_at) AS mes, SUM(valor_total) AS total')
                ->groupBy('anio', 'mes')
                ->orderBy('anio')
                ->orderBy('mes')
                ->get()
                ->keyBy(fn ($r) => $r->anio . '-' . str_pad((string) $r->mes, 2, '0', STR_PAD_LEFT));

            $labels  = [];
            $datos   = [];
            $current = $fechaInicio->copy();

            // Iterar mes a mes dentro del rango personalizado
            while ($current->lte($fechaFin)) {
                $key      = $current->format('Y') . '-' . str_pad((string) $current->month, 2, '0', STR_PAD_LEFT);
                $labels[] = $nombresMeses[$current->month - 1] . ' ' . $current->format('y');
                $datos[]  = isset($resultados[$key]) ? (float) $resultados[$key]->total : 0;
                $current->addMonth();
            }

            return [
                'labels'    => $labels,
                'datos'     => $datos,
                'titulo'    => 'Ingresos mensuales',
                'subtitulo' => $desde . ' al ' . $hasta,
            ];
        }

        // ── Caso 5 (default): mes, mes_pasado → últimos 12 meses ─────────────
        $resultados = Reparacion::entregadas()
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw('
                YEAR(created_at)  AS anio,
                MONTH(created_at) AS mes,
                SUM(valor_total)  AS total
            ')
            ->groupBy('anio', 'mes')
            ->orderBy('anio')
            ->orderBy('mes')
            ->get()
            ->keyBy(fn ($r) => $r->anio . '-' . str_pad((string) $r->mes, 2, '0', STR_PAD_LEFT));

        $labels = [];
        $datos  = [];
        for ($i = 11; $i >= 0; $i--) {
            $fecha    = now()->subMonths($i)->startOfMonth();
            $key      = $fecha->format('Y') . '-' . str_pad((string) $fecha->month, 2, '0', STR_PAD_LEFT);
            $labels[] = $nombresMeses[$fecha->month - 1];
            $datos[]  = isset($resultados[$key]) ? (float) $resultados[$key]->total : 0;
        }

        return [
            'labels'    => $labels,
            'datos'     => $datos,
            'titulo'    => 'Ingresos mensuales',
            'subtitulo' => 'Últimos 12 meses · reparaciones entregadas',
        ];
    }

    /**
     * Obtiene el estado de todas las cajas físicas del local,
     * agrupadas por grupo (A, B, C...).
     *
     * Para cajas ocupadas, incluye el nombre del cliente activo.
     *
     * Estrategia eficiente: 2 consultas fijas en vez de N+1
     *   1. Todas las cajas
     *   2. Reparaciones activas (en_proceso + arreglado) indexadas por caja_id
     *
     * @return \Illuminate\Support\Collection Colección groupBy('grupo')
     */
    private function obtenerEstadoCajas(): \Illuminate\Support\Collection
    {
        // Obtener solo las columnas necesarias de reparaciones activas
        // e indexarlas por caja_id para búsqueda O(1) al mapear las cajas
        $reparacionesActivas = Reparacion::whereIn('estado', [
                EstadoReparacion::EnProceso,
                EstadoReparacion::Arreglado,
            ])
            ->select('caja_id', 'nombre_cliente')
            ->get()
            ->keyBy('caja_id'); // clave = caja_id, valor = {caja_id, nombre_cliente}

        // Obtener todas las cajas ordenadas (A1, A2, B1, B2...) y enriquecer con cliente
        return Caja::orderBy('grupo')
            ->orderBy('numero')
            ->get()
            ->map(function (Caja $caja) use ($reparacionesActivas) {
                // Buscar si esta caja tiene una reparación activa asignada
                $reparacion = $reparacionesActivas->get($caja->id);

                return [
                    'id'             => $caja->id,
                    'nombre_display' => $caja->nombre_display,  // Accessor: "A1", "B3"
                    'grupo'          => $caja->grupo,
                    // Verde solo si NO hay reparación activa (en_proceso/arreglado) en esta caja.
                    // No usamos $caja->estaLibre() porque ese campo puede estar desincronizado;
                    // la fuente de verdad es si existe una reparación activa asignada.
                    'libre'          => $reparacion === null,
                    // El operador ?-> evita error si no hay reparación activa en esta caja
                    'cliente'        => $reparacion?->nombre_cliente,
                ];
            })
            // Agrupar por grupo para que la vista pueda renderizar "Grupo A", "Grupo B"...
            ->groupBy('grupo');
    }

    /**
     * Obtiene el conteo de reparaciones creadas por día en los últimos 30 días.
     * Alimenta la gráfica de área de "Actividad diaria".
     *
     * Retorna arrays listos para Chart.js:
     *   - labels: ['1 Mar', '2 Mar', ..., '30 Mar'] (30 elementos)
     *   - datos:  [3, 7, 2, ..., 5]                 (30 elementos, 0 si no hay)
     */
    private function obtenerReparacionesDiarias(): array
    {
        // Una sola consulta con GROUP BY DATE para los últimos 30 días
        $resultados = Reparacion::select(
                DB::raw('DATE(created_at) as fecha'),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            // Indexar por fecha "YYYY-MM-DD" para búsqueda O(1)
            ->keyBy('fecha');

        $nombresMeses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        $labels = [];
        $datos  = [];

        // Construir array de 30 días: del más antiguo ($i=29) al más reciente ($i=0)
        for ($i = 29; $i >= 0; $i--) {
            $fecha    = now()->subDays($i);
            $fechaKey = $fecha->format('Y-m-d');

            // Formato compacto: "15 Mar" — suficiente para 30 puntos en el eje X
            $labels[] = $fecha->day . ' ' . $nombresMeses[$fecha->month - 1];
            $datos[]  = isset($resultados[$fechaKey]) ? (int) $resultados[$fechaKey]->total : 0;
        }

        return ['labels' => $labels, 'datos' => $datos];
    }

    /**
     * Calcula cuánto se ha cobrado y cuánto queda pendiente
     * de las reparaciones que aún están en el taller (en_proceso + arreglado).
     *
     * Usa dos consultas simples en vez de un JOIN problemático:
     * un JOIN entre reparaciones y abonos multiplicaría valor_total
     * por cada abono, dando un SUM incorrecto.
     *
     * @return object{total_valor, total_cobrado, pendiente, porcentaje}
     */
    private function obtenerEstadisticasCobro(): object
    {
        // Total de lo que vale cobrar por reparaciones activas
        $totalValor = (float) Reparacion::whereIn('estado', [
            EstadoReparacion::EnProceso,
            EstadoReparacion::Arreglado,
        ])->sum('valor_total');

        // Total de abonos ya registrados para esas reparaciones activas
        $totalCobrado = (float) DB::table('abonos')
            ->join('reparaciones', 'abonos.reparacion_id', '=', 'reparaciones.id')
            ->whereIn('reparaciones.estado', ['en_proceso', 'arreglado'])
            ->sum('abonos.monto');

        $pendiente   = $totalValor - $totalCobrado;
        // Porcentaje cobrado: evitar división por cero si no hay reparaciones activas
        $porcentaje  = $totalValor > 0 ? (int) round(($totalCobrado / $totalValor) * 100) : 0;

        return (object) compact('totalValor', 'totalCobrado', 'pendiente', 'porcentaje');
    }
}
