{{--
    Dashboard principal de PipeCell.

    Vista completamente dinámica — todos los datos vienen del DashboardController.
    No hay datos hardcodeados: ingresos, cajas, reparaciones y gráficas
    se calculan en tiempo real desde la base de datos.

    Datos recibidos del controlador:
      $periodo, $desde, $hasta         → parámetros del filtro activo
      $metricas                         → objeto con ingresos, ganancia, completadas
      $cambioPorcentaje                 → array{ingresos, ganancia} o null si no aplica
      $reparacionesActivas              → entero (total sin filtro de fecha)
      $contadores                       → array{en_proceso, arreglado, entregado}
      $ingresosPorMes                   → array{labels, datos} para Chart.js
      $cajasPorGrupo                    → colección agrupada por grupo (A, B, C...)
      $ultimasReparaciones              → colección con las 5 más recientes
      $topMarcas                        → colección con las 5 marcas top
      $maxMarca                         → entero, para calcular % en barras de progreso

    Chart.js se carga desde CDN en la sección @scripts al final.
--}}
@extends('layouts.admin')

@section('page-title', 'Dashboard')

@section('content')

{{-- ============================================================ --}}
{{-- HEADER: Saludo + Formulario de filtro de periodo             --}}
{{-- ============================================================ --}}
<div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-8">

    {{-- Saludo personalizado con el nombre del usuario logueado --}}
    <div>
        <h2 class="text-2xl font-bold text-gray-900">
            Hola, {{ auth()->user()->nombre }}
        </h2>
        <p class="text-gray-500 mt-1">Resumen general del negocio</p>
    </div>

    {{--
        Formulario de filtro de periodo.
        Usa GET para que el periodo quede en la URL y sea compartible/recargable.
        El select hace submit automático al cambiar (excepto "personalizado").
        Los campos de fecha solo se muestran cuando periodo = personalizado.
    --}}
    <form method="GET" action="{{ route('admin.dashboard') }}" id="form-periodo"
          class="flex flex-col sm:flex-row items-start sm:items-center gap-3 flex-wrap">

        {{-- Selector principal de periodo --}}
        <select name="periodo"
                id="select-periodo"
                class="text-sm border border-gray-200 rounded-xl px-4 py-2.5 bg-white text-gray-700
                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                       cursor-pointer">
            <option value="hoy"          @selected($periodo === 'hoy')>Hoy</option>
            <option value="semana"       @selected($periodo === 'semana')>Esta semana</option>
            <option value="mes"          @selected($periodo === 'mes')>Este mes</option>
            <option value="mes_pasado"   @selected($periodo === 'mes_pasado')>Mes pasado</option>
            <option value="trimestre"    @selected($periodo === 'trimestre')>Último trimestre</option>
            <option value="anio"         @selected($periodo === 'anio')>Este año</option>
            <option value="personalizado" @selected($periodo === 'personalizado')>Personalizado</option>
        </select>

        {{--
            Campos de rango personalizado.
            Solo visibles cuando periodo = personalizado.
            La clase 'hidden' se controla por JS y también con Blade
            para que al recargar la página quede en el estado correcto.
        --}}
        <div id="campos-personalizado"
             class="flex items-center gap-2 {{ $periodo !== 'personalizado' ? 'hidden' : '' }}">
            <input type="date"
                   name="desde"
                   value="{{ $desde }}"
                   class="text-sm border border-gray-200 rounded-xl px-3 py-2.5 bg-white text-gray-700
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <span class="text-gray-400 text-sm">al</span>
            <input type="date"
                   name="hasta"
                   value="{{ $hasta }}"
                   class="text-sm border border-gray-200 rounded-xl px-3 py-2.5 bg-white text-gray-700
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            {{-- Botón de aplicar — solo necesario para personalizado ya que los demás hacen auto-submit --}}
            <button type="submit"
                    class="text-sm bg-blue-600 text-white px-4 py-2.5 rounded-xl hover:bg-blue-700
                           transition-colors duration-200 font-medium">
                Aplicar
            </button>
        </div>

    </form>
</div>

{{-- ============================================================ --}}
{{-- TARJETAS DE RESUMEN (4 métricas principales)                 --}}
{{-- ============================================================ --}}
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    {{-- ── Tarjeta 1: Ingresos del periodo ── --}}
    @php
        // Preparar datos del badge de porcentaje para ingresos
        // null = no mostrar badge, positivo = verde arriba, negativo = rojo abajo
        $pctIngresos = $cambioPorcentaje['ingresos'];
    @endphp
    <div class="bg-white rounded-2xl border border-gray-100 p-6 hover:shadow-lg hover:shadow-gray-100/50 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            {{-- Ícono dólar en verde --}}
            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            {{-- Badge de porcentaje vs periodo anterior — solo cuando aplica comparación --}}
            @if ($pctIngresos !== null)
                @if ($pctIngresos >= 0)
                    <div class="flex items-center gap-1 text-xs font-medium text-green-600 bg-green-50 px-2.5 py-1 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                        +{{ $pctIngresos }}%
                    </div>
                @else
                    <div class="flex items-center gap-1 text-xs font-medium text-red-600 bg-red-50 px-2.5 py-1 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                        {{ $pctIngresos }}%
                    </div>
                @endif
            @endif
        </div>
        {{-- Valor formateado en pesos colombianos: $1.234.567 --}}
        <p class="text-3xl font-bold text-gray-900">
            ${{ number_format($metricas->ingresos, 0, ',', '.') }}
        </p>
        <p class="text-sm text-gray-500 mt-1">Ingresos del periodo</p>
    </div>

    {{-- ── Tarjeta 2: Ganancia neta del periodo ── --}}
    @php
        $pctGanancia = $cambioPorcentaje['ganancia'];
    @endphp
    <div class="bg-white rounded-2xl border border-gray-100 p-6 hover:shadow-lg hover:shadow-gray-100/50 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            {{-- Ícono tendencia en púrpura --}}
            <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            {{-- Badge de porcentaje de ganancia vs periodo anterior --}}
            @if ($pctGanancia !== null)
                @if ($pctGanancia >= 0)
                    <div class="flex items-center gap-1 text-xs font-medium text-green-600 bg-green-50 px-2.5 py-1 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                        +{{ $pctGanancia }}%
                    </div>
                @else
                    <div class="flex items-center gap-1 text-xs font-medium text-red-600 bg-red-50 px-2.5 py-1 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                        {{ $pctGanancia }}%
                    </div>
                @endif
            @endif
        </div>
        <p class="text-3xl font-bold text-gray-900">
            ${{ number_format($metricas->ganancia, 0, ',', '.') }}
        </p>
        <p class="text-sm text-gray-500 mt-1">Ganancia neta del periodo</p>
    </div>

    {{-- ── Tarjeta 3: Reparaciones completadas en el periodo ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6 hover:shadow-lg hover:shadow-gray-100/50 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            {{-- Ícono check-circle en azul --}}
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full">
                Entregadas
            </span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $metricas->completadas }}</p>
        <p class="text-sm text-gray-500 mt-1">Reparaciones completadas</p>
    </div>

    {{-- ── Tarjeta 4: Reparaciones activas (en_proceso + arreglado) ── --}}
    {{-- Esta tarjeta NO tiene filtro de fecha — siempre muestra el estado real del taller --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6 hover:shadow-lg hover:shadow-gray-100/50 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            {{-- Ícono reloj en amber --}}
            <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full">
                En taller
            </span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $reparacionesActivas }}</p>
        <p class="text-sm text-gray-500 mt-1">Reparaciones activas</p>
    </div>

</div>

{{-- ============================================================ --}}
{{-- GRÁFICAS: Barras (2/3) + Doughnut (1/3)                      --}}
{{-- ============================================================ --}}
<div class="grid lg:grid-cols-3 gap-6 mb-8">

    {{-- ── Gráfica de barras: Ingresos mensuales (últimos 12 meses) ── --}}
    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h3 class="font-semibold text-gray-900">Ingresos mensuales</h3>
                <p class="text-sm text-gray-500 mt-0.5">Últimos 12 meses</p>
            </div>
            {{-- Leyenda manual (Chart.js legend desactivado para mayor control visual) --}}
            <div class="flex flex-col items-end gap-1.5 text-xs">
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                    <span class="text-gray-500">Reparaciones</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                    <span class="text-gray-400">Ventas</span>
                    {{-- Indicador visual de funcionalidad futura --}}
                    <span class="text-gray-300 text-[10px] italic">próx.</span>
                </div>
            </div>
        </div>
        {{-- Canvas de Chart.js — el alto fijo evita que la gráfica se expanda --}}
        <div class="h-72">
            <canvas id="chart-ingresos"></canvas>
        </div>
    </div>

    {{-- ── Gráfica doughnut: Distribución por estado ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <div class="mb-6">
            <h3 class="font-semibold text-gray-900">Por estado</h3>
            <p class="text-sm text-gray-500 mt-0.5">Total actual de reparaciones</p>
        </div>
        <div class="h-44 flex items-center justify-center">
            <canvas id="chart-estados"></canvas>
        </div>
        {{-- Leyenda manual debajo del doughnut con conteos reales --}}
        <div class="space-y-3 mt-6 pt-4 border-t border-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                    <span class="text-sm text-gray-600">En proceso</span>
                </div>
                <span class="text-sm font-semibold text-gray-900">{{ $contadores['en_proceso'] }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                    <span class="text-sm text-gray-600">Arreglados</span>
                </div>
                <span class="text-sm font-semibold text-gray-900">{{ $contadores['arreglado'] }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                    <span class="text-sm text-gray-600">Entregados</span>
                </div>
                <span class="text-sm font-semibold text-gray-900">{{ $contadores['entregado'] }}</span>
            </div>
        </div>
    </div>

</div>

{{-- ============================================================ --}}
{{-- SECCIÓN INFERIOR: 3 columnas iguales                         --}}
{{-- ============================================================ --}}
<div class="grid lg:grid-cols-3 gap-6">

    {{-- ── Columna 1: Últimas 5 reparaciones ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-900">Últimas reparaciones</h3>
            {{-- Link a la vista completa de reparaciones --}}
            <a href="{{ route('admin.reparaciones.index') }}"
               class="text-xs text-blue-600 hover:text-blue-700 font-medium transition-colors">
                Ver todas →
            </a>
        </div>

        {{-- Lista de las 5 reparaciones más recientes --}}
        <div class="space-y-3">
            @forelse ($ultimasReparaciones as $rep)
                @php
                    // Determinar clases del badge según el estado de la reparación
                    $badgeClase = match ($rep->estado->value) {
                        'en_proceso' => 'bg-amber-50 text-amber-700',
                        'arreglado'  => 'bg-blue-50 text-blue-700',
                        'entregado'  => 'bg-green-50 text-green-700',
                        default      => 'bg-gray-50 text-gray-700',
                    };
                    // Usar la colección de abonos ya cargada (evita N+1 queries)
                    $totalAbonado = $rep->abonos->sum('monto');
                @endphp
                <div class="flex items-start justify-between py-2 border-b border-gray-50 last:border-0">
                    <div class="flex-1 min-w-0 mr-3">
                        {{-- Nombre del cliente (truncado si es muy largo) --}}
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $rep->nombre_cliente }}</p>
                        {{-- Marca + modelo del equipo --}}
                        <p class="text-xs text-gray-400 truncate">
                            {{ $rep->marca }} {{ $rep->modelo }}
                            @if ($rep->caja)
                                · <span class="font-medium">{{ $rep->caja->nombre_display }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        <p class="text-sm font-semibold text-gray-900">
                            ${{ number_format($rep->valor_total, 0, ',', '.') }}
                        </p>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $badgeClase }}">
                            {{ $rep->estado->label() }}
                        </span>
                    </div>
                </div>
            @empty
                {{-- Estado vacío cuando no hay reparaciones aún --}}
                <div class="text-center py-8">
                    <p class="text-sm text-gray-400">No hay reparaciones registradas</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ── Columna 2: Estado de cajas ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-900">Estado de cajas</h3>
            <span class="text-xs text-gray-400">Tiempo real</span>
        </div>

        {{-- Cajas agrupadas por grupo (A, B, C...) --}}
        <div class="space-y-5">
            @forelse ($cajasPorGrupo as $grupo => $cajas)
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
                        Grupo {{ $grupo }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($cajas as $caja)
                            @if ($caja['libre'])
                                {{-- Caja libre: fondo verde --}}
                                <div class="w-12 h-12 rounded-xl bg-green-50 border border-green-200
                                            flex items-center justify-center text-green-700
                                            text-xs font-bold cursor-default"
                                     title="Libre">
                                    {{ $caja['nombre_display'] }}
                                </div>
                            @else
                                {{-- Caja ocupada: fondo rojo, title muestra el nombre del cliente al hover --}}
                                <div class="w-12 h-12 rounded-xl bg-red-50 border border-red-200
                                            flex items-center justify-center text-red-700
                                            text-xs font-bold cursor-default"
                                     title="Ocupada{{ $caja['cliente'] ? ' — ' . $caja['cliente'] : '' }}">
                                    {{ $caja['nombre_display'] }}
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @empty
                {{-- Estado vacío cuando no hay cajas configuradas --}}
                <div class="text-center py-8">
                    <p class="text-sm text-gray-400">No hay cajas configuradas</p>
                </div>
            @endforelse
        </div>

        {{-- Leyenda: conteo de libres y ocupadas --}}
        @php
            // Calcular totales aplanando la colección agrupada
            $totalLibres  = $cajasPorGrupo->flatten(1)->where('libre', true)->count();
            $totalOcupadas = $cajasPorGrupo->flatten(1)->where('libre', false)->count();
        @endphp
        <div class="flex items-center gap-5 mt-6 pt-4 border-t border-gray-100">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded bg-green-200 border border-green-300"></div>
                <span class="text-xs text-gray-500">Libre ({{ $totalLibres }})</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded bg-red-200 border border-red-300"></div>
                <span class="text-xs text-gray-500">Ocupada ({{ $totalOcupadas }})</span>
            </div>
        </div>
    </div>

    {{-- ── Columna 3: Top 5 marcas más reparadas ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-900">Top marcas</h3>
            <span class="text-xs text-gray-400">Todas las reparaciones</span>
        </div>

        <div class="space-y-4">
            @forelse ($topMarcas as $index => $marca)
                @php
                    // Calcular el ancho de la barra proporcional a la marca líder (= 100%)
                    $anchoBarra = $maxMarca > 0
                        ? round(($marca->total / $maxMarca) * 100)
                        : 0;

                    // Colores para las primeras 5 marcas — distintos para diferenciarlas visualmente
                    $coloresBarra = ['bg-blue-500', 'bg-emerald-500', 'bg-amber-500', 'bg-purple-500', 'bg-red-400'];
                    $colorBarra   = $coloresBarra[$index] ?? 'bg-gray-400';
                @endphp
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-2">
                            {{-- Posición en el ranking --}}
                            <span class="text-xs font-bold text-gray-400 w-4">{{ $index + 1 }}</span>
                            <span class="text-sm font-medium text-gray-700">{{ $marca->marca }}</span>
                        </div>
                        {{-- Conteo de reparaciones --}}
                        <span class="text-sm font-semibold text-gray-900">{{ $marca->total }}</span>
                    </div>
                    {{-- Barra de progreso horizontal proporcional --}}
                    <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full {{ $colorBarra }} transition-all duration-500"
                             style="width: {{ $anchoBarra }}%">
                        </div>
                    </div>
                </div>
            @empty
                {{-- Estado vacío --}}
                <div class="text-center py-8">
                    <p class="text-sm text-gray-400">Sin datos aún</p>
                </div>
            @endforelse
        </div>
    </div>

</div>

@endsection

{{-- ============================================================ --}}
{{-- SCRIPTS: Chart.js + inicialización de gráficas               --}}
{{-- ============================================================ --}}
@section('scripts')

{{-- Chart.js desde CDN — solo se carga en esta vista, no en todo el admin --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<script>
    /**
     * Pasar datos de PHP a JavaScript usando la directiva Blade de serialización.
     * Serializa automáticamente a JSON válido y escapa caracteres peligrosos.
     *
     * datosIngresos: { labels: ['Ene', ...], datos: [1800000, ...] }
     * datosEstados:  { en_proceso: 8, arreglado: 5, entregado: 23 }
     */
    const datosIngresos = @json($ingresosPorMes);
    const datosEstados  = @json($contadores);

    // ─────────────────────────────────────────────────────────────
    // GRÁFICA 1: Barras — Ingresos mensuales (últimos 12 meses)
    // ─────────────────────────────────────────────────────────────
    const ctxIngresos = document.getElementById('chart-ingresos').getContext('2d');

    new Chart(ctxIngresos, {
        type: 'bar',
        data: {
            // Las etiquetas y datos vienen del servidor (ya están en el orden correcto)
            labels: datosIngresos.labels,
            datasets: [
                {
                    // Dataset 1: Ingresos reales de reparaciones entregadas
                    label: 'Reparaciones',
                    data: datosIngresos.datos,
                    backgroundColor: 'rgba(59, 130, 246, 0.85)',
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    // Dataset 2: RESERVADO para ventas — todo en cero hasta implementar el módulo
                    // Cuando se implemente ventas, solo hay que reemplazar este array de ceros
                    label: 'Ventas',
                    data: new Array(datosIngresos.labels.length).fill(0),
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderRadius: 6,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            // Al pasar el cursor muestra ambos datasets del mismo mes en el tooltip
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                // Ocultamos la leyenda de Chart.js — tenemos la nuestra en HTML para más control
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 13 },
                    bodyFont: { size: 12 },
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        // Formato de moneda colombiana en el tooltip: $1.234.567
                        label: function (ctx) {
                            const valor = ctx.parsed.y;
                            // Si el dataset de ventas está en 0, lo omitimos del tooltip
                            if (ctx.dataset.label === 'Ventas' && valor === 0) {
                                return ctx.dataset.label + ': próximamente';
                            }
                            return ctx.dataset.label + ': $' + valor.toLocaleString('es-CO');
                        }
                    }
                }
            },
            scales: {
                x: {
                    // Sin líneas de cuadrícula verticales — más limpio visualmente
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 12 } }
                },
                y: {
                    grid: { color: '#f1f5f9' },
                    border: { display: false },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 11 },
                        // Formato corto en el eje Y: $1.8M, $500K, $0
                        callback: function (value) {
                            if (value === 0) return '$0';
                            if (value >= 1_000_000) return '$' + (value / 1_000_000).toFixed(1) + 'M';
                            if (value >= 1_000)     return '$' + (value / 1_000).toFixed(0) + 'K';
                            return '$' + value;
                        }
                    }
                }
            }
        }
    });

    // ─────────────────────────────────────────────────────────────
    // GRÁFICA 2: Doughnut — Distribución por estado
    // ─────────────────────────────────────────────────────────────
    const ctxEstados = document.getElementById('chart-estados').getContext('2d');

    new Chart(ctxEstados, {
        type: 'doughnut',
        data: {
            labels: ['En proceso', 'Arreglados', 'Entregados'],
            datasets: [{
                // Los datos vienen del servidor — mismo orden que los labels
                data: [
                    datosEstados.en_proceso,
                    datosEstados.arreglado,
                    datosEstados.entregado,
                ],
                backgroundColor: [
                    'rgba(251, 191, 36, 0.9)',   // Amber — en proceso
                    'rgba(59, 130, 246, 0.9)',    // Blue  — arreglado
                    'rgba(16, 185, 129, 0.9)',    // Green — entregado
                ],
                borderWidth: 0,
                spacing: 3,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',   // Agujero central grande para el estilo moderno
            plugins: {
                // La leyenda está en HTML para mayor control de diseño
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    cornerRadius: 10,
                }
            }
        }
    });

    // ─────────────────────────────────────────────────────────────
    // LÓGICA DEL FILTRO DE PERIODO
    // ─────────────────────────────────────────────────────────────
    const selectPeriodo       = document.getElementById('select-periodo');
    const camposPersonalizado = document.getElementById('campos-personalizado');

    /**
     * Al cambiar el select de periodo:
     *   - Si es 'personalizado': mostrar los campos de fecha y NO hacer submit automático
     *     (el usuario necesita elegir las fechas primero)
     *   - Para cualquier otro periodo: ocultar campos de fecha y hacer submit inmediato
     */
    selectPeriodo.addEventListener('change', function () {
        if (this.value === 'personalizado') {
            // Mostrar los campos de fecha para que el usuario pueda elegirlas
            camposPersonalizado.classList.remove('hidden');
            // No hacemos submit aquí — esperar que el usuario haga clic en "Aplicar"
        } else {
            // Ocultar campos de fecha que no aplican
            camposPersonalizado.classList.add('hidden');
            // Auto-submit: el cambio de periodo se aplica de inmediato
            this.form.submit();
        }
    });
</script>

@endsection
