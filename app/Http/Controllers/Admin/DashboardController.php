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
        // Una sola consulta con GROUP BY en vez de 3 consultas separadas
        $contadoresBD = Reparacion::select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $contadores = [
            'en_proceso' => (int) ($contadoresBD['en_proceso'] ?? 0),
            'arreglado'  => (int) ($contadoresBD['arreglado'] ?? 0),
            'entregado'  => (int) ($contadoresBD['entregado'] ?? 0),
        ];

        // ── 4. INGRESOS POR MES (últimos 12 meses para la gráfica de barras) ──
        $ingresosPorMes = $this->obtenerIngresosPorMes();

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

        // Valor máximo de la marca #1 para calcular el ancho de las barras de progreso en %
        $maxMarca = $topMarcas->max('total') ?: 1;

        return view('admin.dashboard', compact(
            'periodo', 'desde', 'hasta',
            'metricas', 'cambioPorcentaje',
            'reparacionesActivas', 'contadores',
            'ingresosPorMes', 'cajasPorGrupo',
            'ultimasReparaciones', 'topMarcas', 'maxMarca'
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
     * Obtiene los ingresos mensuales de los últimos 12 meses
     * agrupados para alimentar la gráfica de barras de Chart.js.
     *
     * Retorna un array con dos sub-arrays:
     *   - labels: ['Ene', 'Feb', ..., 'Dic'] (12 elementos)
     *   - datos:  [1800000, 2100000, ..., 0]  (12 elementos, 0 si no hay datos)
     *
     * Se incluyen los 12 meses aunque algunos tengan valor 0,
     * para que la gráfica siempre muestre el año completo.
     */
    private function obtenerIngresosPorMes(): array
    {
        // Consultar la BD: ingresos agrupados por año y mes en el período
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
            // Indexar por "YYYY-MM" para búsqueda O(1) al armar el array de 12 elementos
            ->keyBy(fn ($r) => $r->anio . '-' . str_pad((string) $r->mes, 2, '0', STR_PAD_LEFT));

        // Nombres abreviados de los 12 meses en español
        $nombresMeses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        $labels = [];
        $datos  = [];

        // Construir el array de 12 posiciones, del mes más antiguo al más reciente
        // $i = 11 → el mes de hace 11 meses (el más viejo en la gráfica, eje X izquierda)
        // $i = 0  → el mes actual (el más reciente, eje X derecha)
        for ($i = 11; $i >= 0; $i--) {
            $fecha = now()->subMonths($i)->startOfMonth();
            $key   = $fecha->format('Y') . '-' . str_pad((string) $fecha->month, 2, '0', STR_PAD_LEFT);

            $labels[] = $nombresMeses[$fecha->month - 1];
            // Si no hay datos para ese mes, usamos 0 (gráfica no queda vacía)
            $datos[]  = isset($resultados[$key]) ? (float) $resultados[$key]->total : 0;
        }

        return [
            'labels' => $labels,
            'datos'  => $datos,
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
}
