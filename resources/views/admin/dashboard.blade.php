{{--
    Dashboard profesional de PipeCell — versión 2.

    Gráficas incluidas:
      1. Barras agrupadas  → Ingresos mensuales (últimos 12 meses)
      2. Doughnut          → Distribución de reparaciones por estado
      3. Área con gradiente → Actividad diaria (últimos 30 días)   ← NUEVA
      4. Barras horizontales → Top marcas más reparadas             ← NUEVA

    Datos recibidos del controlador (DashboardController):
      $periodo, $desde, $hasta
      $metricas               → {ingresos, ganancia, completadas}
      $cambioPorcentaje       → {ingresos: float|null, ganancia: float|null}
      $reparacionesActivas    → int
      $contadores             → {en_proceso, arreglado, entregado}
      $ingresosPorMes         → {labels[], datos[]}
      $reparacionesDiarias    → {labels[], datos[]}
      $topMarcasChart         → {labels[], datos[]}
      $cajasPorGrupo          → Collection agrupada por grupo
      $ultimasReparaciones    → Collection (5 últimas)
      $estadisticasCobro      → {totalValor, totalCobrado, pendiente, porcentaje}
--}}
@extends('layouts.admin')

@section('page-title', 'Dashboard')

@section('content')

{{-- ============================================================ --}}
{{-- HEADER: Saludo + Formulario de filtro de periodo             --}}
{{-- ============================================================ --}}
<div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-8">

    <div>
        <h2 class="text-2xl font-bold text-gray-900">
            Hola, {{ auth()->user()->nombre }} 👋
        </h2>
        <p class="text-gray-500 mt-1 text-sm">Panel de control · PipeCell</p>
    </div>

    {{-- Filtro GET: el cambio de periodo recarga la página con los datos correctos --}}
    <form method="GET" action="{{ route('admin.dashboard') }}" id="form-periodo"
          class="flex flex-col sm:flex-row items-start sm:items-center gap-3 flex-wrap">

        <select name="periodo" id="select-periodo"
                class="text-sm border border-gray-200 rounded-xl px-4 py-2.5 bg-white text-gray-700
                       focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400
                       cursor-pointer shadow-sm">
            <option value="hoy"           @selected($periodo === 'hoy')>Hoy</option>
            <option value="semana"        @selected($periodo === 'semana')>Esta semana</option>
            <option value="mes"           @selected($periodo === 'mes')>Este mes</option>
            <option value="mes_pasado"    @selected($periodo === 'mes_pasado')>Mes pasado</option>
            <option value="trimestre"     @selected($periodo === 'trimestre')>Último trimestre</option>
            <option value="anio"          @selected($periodo === 'anio')>Este año</option>
            <option value="personalizado" @selected($periodo === 'personalizado')>Personalizado</option>
        </select>

        {{-- Campos de rango: visibles solo con periodo=personalizado --}}
        <div id="campos-personalizado"
             class="flex items-center gap-2 {{ $periodo !== 'personalizado' ? 'hidden' : '' }}">
            <input type="date" name="desde" value="{{ $desde }}"
                   class="text-sm border border-gray-200 rounded-xl px-3 py-2.5 bg-white text-gray-700
                          focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm">
            <span class="text-gray-400 text-sm">al</span>
            <input type="date" name="hasta" value="{{ $hasta }}"
                   class="text-sm border border-gray-200 rounded-xl px-3 py-2.5 bg-white text-gray-700
                          focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm">
            <button type="submit"
                    class="text-sm bg-blue-600 text-white px-4 py-2.5 rounded-xl hover:bg-blue-700
                           transition-colors font-medium shadow-sm">
                Aplicar
            </button>
        </div>

    </form>
</div>

{{-- ============================================================ --}}
{{-- FILA 1: TARJETAS KPI (4 métricas principales)               --}}
{{-- ============================================================ --}}
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">

    {{-- ── KPI 1: Ingresos del periodo ── --}}
    @php $pctIngresos = $cambioPorcentaje['ingresos']; @endphp
    <div class="bg-white rounded-2xl border border-gray-100 p-6 relative overflow-hidden
                hover:shadow-xl hover:shadow-gray-100/80 transition-all duration-300 group">
        {{-- Línea de acento superior verde --}}
        <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-emerald-400 to-green-500 rounded-t-2xl"></div>
        <div class="flex items-start justify-between mb-5">
            {{-- Ícono con gradiente y sombra de color --}}
            <div class="w-11 h-11 bg-gradient-to-br from-emerald-400 to-green-600 rounded-xl
                        flex items-center justify-center shadow-lg shadow-emerald-200/60
                        group-hover:scale-110 transition-transform duration-300">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            {{-- Badge de variación (solo cuando hay comparación disponible) --}}
            @if ($pctIngresos !== null)
                @if ($pctIngresos >= 0)
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700
                                 bg-emerald-50 border border-emerald-100 px-2.5 py-1 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                        +{{ $pctIngresos }}%
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-red-600
                                 bg-red-50 border border-red-100 px-2.5 py-1 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                        {{ $pctIngresos }}%
                    </span>
                @endif
            @endif
        </div>
        <p class="text-2xl font-bold text-gray-900 tracking-tight">
            ${{ number_format($metricas->ingresos, 0, ',', '.') }}
        </p>
        <p class="text-sm text-gray-500 mt-1 font-medium">Ingresos del periodo</p>
    </div>

    {{-- ── KPI 2: Ganancia neta ── --}}
    @php $pctGanancia = $cambioPorcentaje['ganancia']; @endphp
    <div class="bg-white rounded-2xl border border-gray-100 p-6 relative overflow-hidden
                hover:shadow-xl hover:shadow-gray-100/80 transition-all duration-300 group">
        <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-violet-400 to-purple-600 rounded-t-2xl"></div>
        <div class="flex items-start justify-between mb-5">
            <div class="w-11 h-11 bg-gradient-to-br from-violet-400 to-purple-600 rounded-xl
                        flex items-center justify-center shadow-lg shadow-violet-200/60
                        group-hover:scale-110 transition-transform duration-300">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            @if ($pctGanancia !== null)
                @if ($pctGanancia >= 0)
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700
                                 bg-emerald-50 border border-emerald-100 px-2.5 py-1 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                        +{{ $pctGanancia }}%
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-red-600
                                 bg-red-50 border border-red-100 px-2.5 py-1 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                        {{ $pctGanancia }}%
                    </span>
                @endif
            @endif
        </div>
        <p class="text-2xl font-bold text-gray-900 tracking-tight">
            ${{ number_format($metricas->ganancia, 0, ',', '.') }}
        </p>
        <p class="text-sm text-gray-500 mt-1 font-medium">Ganancia neta</p>
    </div>

    {{-- ── KPI 3: Reparaciones completadas ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6 relative overflow-hidden
                hover:shadow-xl hover:shadow-gray-100/80 transition-all duration-300 group">
        <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-blue-400 to-indigo-500 rounded-t-2xl"></div>
        <div class="flex items-start justify-between mb-5">
            <div class="w-11 h-11 bg-gradient-to-br from-blue-400 to-indigo-600 rounded-xl
                        flex items-center justify-center shadow-lg shadow-blue-200/60
                        group-hover:scale-110 transition-transform duration-300">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-2.5 py-1 rounded-full">
                Entregadas
            </span>
        </div>
        <p class="text-2xl font-bold text-gray-900 tracking-tight">{{ $metricas->completadas }}</p>
        <p class="text-sm text-gray-500 mt-1 font-medium">Reparaciones completadas</p>
    </div>

    {{-- ── KPI 4: Reparaciones activas en taller ── --}}
    {{-- Sin filtro de fecha: refleja el estado real del taller en este momento --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6 relative overflow-hidden
                hover:shadow-xl hover:shadow-gray-100/80 transition-all duration-300 group">
        <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-amber-400 to-orange-500 rounded-t-2xl"></div>
        <div class="flex items-start justify-between mb-5">
            <div class="w-11 h-11 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl
                        flex items-center justify-center shadow-lg shadow-amber-200/60
                        group-hover:scale-110 transition-transform duration-300">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-100 px-2.5 py-1 rounded-full">
                En taller
            </span>
        </div>
        <p class="text-2xl font-bold text-gray-900 tracking-tight">{{ $reparacionesActivas }}</p>
        <p class="text-sm text-gray-500 mt-1 font-medium">Reparaciones activas</p>
    </div>

</div>

{{-- ============================================================ --}}
{{-- FILA 2: Barras mensuales (2/3) + Doughnut de estados (1/3)  --}}
{{-- ============================================================ --}}
<div class="grid lg:grid-cols-3 gap-5 mb-6">

    {{-- ── Gráfica de barras: Ingresos de los últimos 12 meses ── --}}
    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6
                hover:shadow-xl hover:shadow-gray-100/50 transition-all duration-300">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h3 class="font-semibold text-gray-900">{{ $ingresosPorMes['titulo'] }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $ingresosPorMes['subtitulo'] }}</p>
            </div>
            <div class="flex items-center gap-4 text-xs">
                <div class="flex items-center gap-1.5">
                    <div class="w-2.5 h-2.5 rounded-sm bg-blue-500"></div>
                    <span class="text-gray-500 font-medium">Reparaciones</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></div>
                    <span class="text-gray-400 font-medium">Ventas</span>
                    <span class="text-[10px] text-gray-300 italic">próx.</span>
                </div>
            </div>
        </div>
        <div class="h-64">
            <canvas id="chart-ingresos"></canvas>
        </div>
    </div>

    {{-- ── Gráfica doughnut: Distribución por estado ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6
                hover:shadow-xl hover:shadow-gray-100/50 transition-all duration-300">
        <div class="mb-5">
            <h3 class="font-semibold text-gray-900">Por estado</h3>
            <p class="text-xs text-gray-400 mt-0.5">Distribución del periodo seleccionado</p>
        </div>
        {{-- Centro del doughnut: total de reparaciones --}}
        <div class="relative h-44 flex items-center justify-center">
            <canvas id="chart-estados" class="absolute inset-0"></canvas>
            {{-- Número total superpuesto en el centro del doughnut --}}
            <div class="relative z-10 text-center pointer-events-none">
                <p class="text-2xl font-bold text-gray-900 leading-none">
                    {{ array_sum($contadores) }}
                </p>
                <p class="text-[10px] text-gray-400 mt-0.5 uppercase tracking-wide font-medium">total</p>
            </div>
        </div>
        {{-- Leyenda con conteos reales --}}
        <div class="space-y-2.5 mt-5 pt-4 border-t border-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-amber-400 shrink-0"></div>
                    <span class="text-sm text-gray-600">En proceso</span>
                </div>
                <span class="text-sm font-bold text-gray-900">{{ $contadores['en_proceso'] }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-blue-500 shrink-0"></div>
                    <span class="text-sm text-gray-600">Arreglados</span>
                </div>
                <span class="text-sm font-bold text-gray-900">{{ $contadores['arreglado'] }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></div>
                    <span class="text-sm text-gray-600">Entregados</span>
                </div>
                <span class="text-sm font-bold text-gray-900">{{ $contadores['entregado'] }}</span>
            </div>
        </div>
    </div>

</div>

{{-- ============================================================ --}}
{{-- FILA 3 (NUEVA): Área diaria (1/2) + Barras horizontales (1/2) --}}
{{-- ============================================================ --}}
<div class="grid lg:grid-cols-2 gap-5 mb-6">

    {{-- ── Gráfica de área: Actividad diaria (últimos 30 días) ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6
                hover:shadow-xl hover:shadow-gray-100/50 transition-all duration-300">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h3 class="font-semibold text-gray-900">Actividad diaria</h3>
                <p class="text-xs text-gray-400 mt-0.5">Reparaciones ingresadas · últimos 30 días</p>
            </div>
            {{-- Indicador del total del período --}}
            @php $totalDiario = array_sum($reparacionesDiarias['datos']); @endphp
            <div class="text-right">
                <p class="text-lg font-bold text-gray-900 leading-none">{{ $totalDiario }}</p>
                <p class="text-[10px] text-gray-400 mt-0.5 uppercase tracking-wide">30 días</p>
            </div>
        </div>
        <div class="h-52">
            <canvas id="chart-diario"></canvas>
        </div>
    </div>

    {{-- ── Gráfica de barras horizontales: Top marcas ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6
                hover:shadow-xl hover:shadow-gray-100/50 transition-all duration-300">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h3 class="font-semibold text-gray-900">Top marcas</h3>
                <p class="text-xs text-gray-400 mt-0.5">Las 5 más reparadas · histórico</p>
            </div>
        </div>
        <div class="h-52">
            <canvas id="chart-marcas"></canvas>
        </div>
    </div>

</div>

{{-- ============================================================ --}}
{{-- FILA 4: Últimas reparaciones + Cajas + Cobros pendientes     --}}
{{-- ============================================================ --}}
<div class="grid lg:grid-cols-3 gap-5">

    {{-- ── Columna 1: Últimas 5 reparaciones ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6
                hover:shadow-xl hover:shadow-gray-100/50 transition-all duration-300">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-900">Últimas reparaciones</h3>
            <a href="{{ route('admin.reparaciones.index') }}"
               class="text-xs text-blue-600 hover:text-blue-700 font-semibold transition-colors">
                Ver todas →
            </a>
        </div>

        <div class="space-y-3">
            @forelse ($ultimasReparaciones as $rep)
                @php
                    // Clases del badge según estado
                    $badgeClase = match ($rep->estado->value) {
                        'en_proceso' => 'bg-amber-50 text-amber-700 border-amber-100',
                        'arreglado'  => 'bg-blue-50 text-blue-700 border-blue-100',
                        'entregado'  => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                        default      => 'bg-gray-50 text-gray-700 border-gray-100',
                    };
                @endphp
                <div class="flex items-start justify-between py-2.5 border-b border-gray-50 last:border-0">
                    <div class="flex-1 min-w-0 mr-3">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $rep->nombre_cliente }}</p>
                        <p class="text-xs text-gray-400 truncate mt-0.5">
                            {{ $rep->marca }} {{ $rep->modelo }}
                            @if ($rep->caja)
                                ·
                                <span class="font-semibold text-gray-600">{{ $rep->caja->nombre_display }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-1.5 shrink-0">
                        <p class="text-sm font-bold text-gray-900">
                            ${{ number_format($rep->valor_total, 0, ',', '.') }}
                        </p>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full border {{ $badgeClase }}">
                            {{ $rep->estado->label() }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-10 text-center">
                    <div class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-400">Sin reparaciones aún</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ── Columna 2: Estado de cajas ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6
                hover:shadow-xl hover:shadow-gray-100/50 transition-all duration-300">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-900">Estado de cajas</h3>
            <span class="text-xs text-gray-400 font-medium">Tiempo real</span>
        </div>

        <div class="space-y-4">
            @forelse ($cajasPorGrupo as $grupo => $cajas)
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2.5">
                        Grupo {{ $grupo }}
                    </p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($cajas as $caja)
                            @if ($caja['libre'])
                                {{-- Caja libre: verde con ícono de check sutil --}}
                                <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-200
                                            flex items-center justify-center text-emerald-700
                                            text-xs font-bold cursor-default hover:bg-emerald-100 transition-colors"
                                     title="Libre">
                                    {{ $caja['nombre_display'] }}
                                </div>
                            @else
                                {{-- Caja ocupada: roja, al hover muestra el cliente --}}
                                <div class="w-11 h-11 rounded-xl bg-red-50 border border-red-200
                                            flex items-center justify-center text-red-700
                                            text-xs font-bold cursor-default hover:bg-red-100 transition-colors"
                                     title="Ocupada{{ $caja['cliente'] ? ' — ' . $caja['cliente'] : '' }}">
                                    {{ $caja['nombre_display'] }}
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <p class="text-sm text-gray-400">No hay cajas configuradas</p>
                </div>
            @endforelse
        </div>

        {{-- Leyenda con conteos calculados desde los datos reales --}}
        @php
            $flatCajas     = $cajasPorGrupo->flatten(1);
            $totalLibres   = $flatCajas->where('libre', true)->count();
            $totalOcupadas = $flatCajas->where('libre', false)->count();
        @endphp
        <div class="flex items-center gap-5 mt-5 pt-4 border-t border-gray-100">
            <div class="flex items-center gap-1.5">
                <div class="w-2.5 h-2.5 rounded bg-emerald-200 border border-emerald-300"></div>
                <span class="text-xs text-gray-500 font-medium">Libre ({{ $totalLibres }})</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-2.5 h-2.5 rounded bg-red-200 border border-red-300"></div>
                <span class="text-xs text-gray-500 font-medium">Ocupada ({{ $totalOcupadas }})</span>
            </div>
        </div>
    </div>

    {{-- ── Columna 3: Cobros pendientes en reparaciones activas ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6
                hover:shadow-xl hover:shadow-gray-100/50 transition-all duration-300">
        <div class="mb-5">
            <h3 class="font-semibold text-gray-900">Cobros pendientes</h3>
            <p class="text-xs text-gray-400 mt-0.5">Reparaciones activas en taller</p>
        </div>

        {{-- Porcentaje cobrado grande --}}
        <div class="flex items-end gap-3 mb-4">
            <span class="text-4xl font-bold text-gray-900 leading-none tracking-tight">
                {{ $estadisticasCobro->porcentaje }}%
            </span>
            <div class="mb-0.5">
                <p class="text-xs text-gray-500 leading-snug font-medium">cobrado</p>
                <p class="text-xs text-gray-400 leading-snug">del total activo</p>
            </div>
        </div>

        {{-- Barra de progreso con gradiente --}}
        <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden mb-5">
            <div class="h-full bg-gradient-to-r from-indigo-500 to-blue-400 rounded-full
                        transition-all duration-700"
                 style="width: {{ min(100, max(2, $estadisticasCobro->porcentaje)) }}%">
            </div>
        </div>

        {{-- Desglose de valores --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-indigo-500 shrink-0"></div>
                    <span class="text-sm text-gray-600">Cobrado</span>
                </div>
                <span class="text-sm font-bold text-gray-900">
                    ${{ number_format($estadisticasCobro->totalCobrado, 0, ',', '.') }}
                </span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></div>
                    <span class="text-sm text-gray-600">Por cobrar</span>
                </div>
                <span class="text-sm font-bold text-amber-600">
                    ${{ number_format($estadisticasCobro->pendiente, 0, ',', '.') }}
                </span>
            </div>
            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                <span class="text-sm font-semibold text-gray-700">Total activo</span>
                <span class="text-sm font-bold text-gray-900">
                    ${{ number_format($estadisticasCobro->totalValor, 0, ',', '.') }}
                </span>
            </div>
        </div>

        {{-- Mensaje cuando no hay reparaciones activas --}}
        @if ($reparacionesActivas === 0)
            <div class="mt-4 pt-3 border-t border-gray-50 text-center">
                <p class="text-xs text-gray-400 italic">Sin reparaciones activas en taller</p>
            </div>
        @endif
    </div>

</div>

@endsection

{{-- ============================================================ --}}
{{-- SCRIPTS: Chart.js + 4 gráficas                               --}}
{{-- ============================================================ --}}
@section('scripts')

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<script>
    // ─── Datos del servidor → JavaScript ────────────────────────
    // La directiva de serialización de Blade produce JSON válido y seguro
    const datosIngresos  = @json($ingresosPorMes);
    const datosEstados   = @json($contadores);
    const datosDiarios   = @json($reparacionesDiarias);
    const datosMarcas    = @json($topMarcasChart);

    // ─── Helpers reutilizables ───────────────────────────────────

    /**
     * Formatea un número como moneda colombiana: $1.234.567
     * Usado en los tooltips de todas las gráficas financieras.
     */
    function formatCOP(valor) {
        return '$' + Math.round(valor).toLocaleString('es-CO');
    }

    /**
     * Estilo base compartido por todos los tooltips.
     * Mantiene consistencia visual entre las 4 gráficas.
     */
    const tooltipBase = {
        backgroundColor: '#0f172a',
        titleColor: '#e2e8f0',
        bodyColor: '#94a3b8',
        borderColor: '#1e293b',
        borderWidth: 1,
        padding: 12,
        cornerRadius: 10,
        titleFont: { size: 12, weight: '600' },
        bodyFont: { size: 12 },
        displayColors: true,
        boxWidth: 10,
        boxHeight: 10,
        boxPadding: 4,
    };

    // ════════════════════════════════════════════════════════════
    // GRÁFICA 1: Barras agrupadas — Ingresos mensuales
    // ════════════════════════════════════════════════════════════
    new Chart(document.getElementById('chart-ingresos'), {
        type: 'bar',
        data: {
            labels: datosIngresos.labels,
            datasets: [
                {
                    label: 'Reparaciones',
                    data: datosIngresos.datos,
                    backgroundColor: 'rgba(99, 102, 241, 0.85)',
                    hoverBackgroundColor: 'rgba(99, 102, 241, 1)',
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    // Dataset reservado para ventas — en cero hasta implementar el módulo
                    label: 'Ventas',
                    data: new Array(datosIngresos.labels.length).fill(0),
                    backgroundColor: 'rgba(16, 185, 129, 0.75)',
                    hoverBackgroundColor: 'rgba(16, 185, 129, 1)',
                    borderRadius: 6,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...tooltipBase,
                    callbacks: {
                        label: (ctx) => {
                            if (ctx.dataset.label === 'Ventas' && ctx.parsed.y === 0) {
                                return '  Ventas: próximamente';
                            }
                            return '  ' + ctx.dataset.label + ': ' + formatCOP(ctx.parsed.y);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 11 } },
                    border: { display: false },
                },
                y: {
                    grid: { color: '#f8fafc', lineWidth: 1 },
                    border: { display: false },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 10 },
                        // Formato corto: $1.8M, $500K
                        callback: (v) => {
                            if (v === 0) return '$0';
                            if (v >= 1_000_000) return '$' + (v / 1_000_000).toFixed(1) + 'M';
                            if (v >= 1_000)     return '$' + (v / 1_000).toFixed(0) + 'K';
                            return '$' + v;
                        }
                    }
                }
            }
        }
    });

    // ════════════════════════════════════════════════════════════
    // GRÁFICA 2: Doughnut — Distribución por estado
    // ════════════════════════════════════════════════════════════
    new Chart(document.getElementById('chart-estados'), {
        type: 'doughnut',
        data: {
            labels: ['En proceso', 'Arreglados', 'Entregados'],
            datasets: [{
                data: [datosEstados.en_proceso, datosEstados.arreglado, datosEstados.entregado],
                backgroundColor: [
                    'rgba(251, 191, 36, 0.9)',    // Amber — en proceso
                    'rgba(99, 102, 241, 0.9)',     // Indigo — arreglado
                    'rgba(16, 185, 129, 0.9)',     // Emerald — entregado
                ],
                hoverBackgroundColor: [
                    'rgba(251, 191, 36, 1)',
                    'rgba(99, 102, 241, 1)',
                    'rgba(16, 185, 129, 1)',
                ],
                borderWidth: 2,
                borderColor: '#ffffff',
                spacing: 3,
                borderRadius: 5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '74%',
            plugins: {
                legend: { display: false },
                tooltip: { ...tooltipBase }
            }
        }
    });

    // ════════════════════════════════════════════════════════════
    // GRÁFICA 3 (NUEVA): Área con gradiente — Actividad diaria
    // ════════════════════════════════════════════════════════════
    const canvasDiario = document.getElementById('chart-diario');

    new Chart(canvasDiario, {
        type: 'line',
        data: {
            labels: datosDiarios.labels,
            datasets: [{
                label: 'Reparaciones',
                data: datosDiarios.datos,
                fill: true,
                // Gradiente vertical: indigo semitransparente arriba → transparente abajo
                backgroundColor: (context) => {
                    const chart = context.chart;
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return 'rgba(99, 102, 241, 0.1)';
                    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.22)');
                    gradient.addColorStop(0.6, 'rgba(99, 102, 241, 0.06)');
                    gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');
                    return gradient;
                },
                borderColor: 'rgba(99, 102, 241, 0.9)',
                borderWidth: 2,
                tension: 0.45,          // Curvas suaves tipo spline
                pointRadius: 0,         // Sin puntos para un look limpio
                pointHoverRadius: 5,
                pointHoverBackgroundColor: '#6366f1',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 2.5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...tooltipBase,
                    callbacks: {
                        label: (ctx) => '  Reparaciones: ' + ctx.parsed.y,
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 10 },
                        maxTicksLimit: 10,  // Mostrar solo ~10 fechas para no saturar el eje
                        maxRotation: 0,
                    }
                },
                y: {
                    grid: { color: '#f8fafc' },
                    border: { display: false },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 10 },
                        stepSize: 1,       // Solo números enteros (no puede haber 2.5 reparaciones)
                        precision: 0,
                    },
                    min: 0,
                }
            }
        }
    });

    // ════════════════════════════════════════════════════════════
    // GRÁFICA 4 (NUEVA): Barras horizontales — Top marcas
    // ════════════════════════════════════════════════════════════
    new Chart(document.getElementById('chart-marcas'), {
        type: 'bar',
        data: {
            labels: datosMarcas.labels,
            datasets: [{
                label: 'Reparaciones',
                data: datosMarcas.datos,
                // Paleta de colores distintos para cada marca (máximo 5)
                backgroundColor: [
                    'rgba(99, 102, 241, 0.85)',
                    'rgba(16, 185, 129, 0.85)',
                    'rgba(245, 158, 11, 0.85)',
                    'rgba(239, 68, 68, 0.75)',
                    'rgba(14, 165, 233, 0.85)',
                ],
                hoverBackgroundColor: [
                    'rgba(99, 102, 241, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(245, 158, 11, 1)',
                    'rgba(239, 68, 68, 1)',
                    'rgba(14, 165, 233, 1)',
                ],
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            indexAxis: 'y',     // Barras horizontales
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...tooltipBase,
                    callbacks: {
                        label: (ctx) => '  Total: ' + ctx.parsed.x + ' reparaciones',
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: '#f8fafc' },
                    border: { display: false },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 10 },
                        precision: 0,
                        stepSize: 1,
                    }
                },
                y: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        color: '#374151',
                        font: { size: 12, weight: '500' },
                    }
                }
            }
        }
    });

    // ─── Toggle de campos de fecha personalizado ─────────────────
    const selectPeriodo       = document.getElementById('select-periodo');
    const camposPersonalizado = document.getElementById('campos-personalizado');

    selectPeriodo.addEventListener('change', function () {
        if (this.value === 'personalizado') {
            // Mostrar campos de fecha — el usuario hace submit con el botón "Aplicar"
            camposPersonalizado.classList.remove('hidden');
        } else {
            // Ocultar campos y hacer submit inmediato para cualquier otro periodo
            camposPersonalizado.classList.add('hidden');
            this.form.submit();
        }
    });
</script>

@endsection
