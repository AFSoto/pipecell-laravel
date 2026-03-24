{{--
    Dashboard del panel de administración.

    Vista principal con métricas del negocio.
    Incluye: tarjetas de resumen, gráfica de ingresos por mes,
    gráfica de reparaciones por estado, filtros de fecha,
    tabla de últimas reparaciones y estado de cajas.

    Chart.js se carga desde CDN para las gráficas.
    Los datos están hardcodeados por ahora — se conectarán
    a consultas reales cuando existan reparaciones.
--}}
@extends('layouts.admin')

@section('page-title', 'Dashboard')

@section('content')

{{-- Saludo + Filtros --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">
            Hola, {{ auth()->user()->nombre }}
        </h2>
        <p class="text-gray-500 mt-1">Resumen general del negocio</p>
    </div>

    {{--
        Filtros de fecha.
        Por ahora son visuales — cuando conectemos datos reales,
        estos filtros harán peticiones al servidor.
    --}}
    <div class="flex items-center gap-3">
        {{-- Selector de rango rápido --}}
        <select id="filter-range" onchange="updateFilter(this.value)"
                class="text-sm border border-gray-200 rounded-xl px-4 py-2.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="hoy">Hoy</option>
            <option value="semana">Esta semana</option>
            <option value="mes" selected>Este mes</option>
            <option value="anio">Este año</option>
        </select>

        {{-- Selector de tipo --}}
        <select id="filter-type"
                class="text-sm border border-gray-200 rounded-xl px-4 py-2.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="todo">Todo</option>
            <option value="reparaciones">Reparaciones</option>
            <option value="ventas">Ventas</option>
        </select>
    </div>
</div>

{{-- ============================== --}}
{{-- TARJETAS DE RESUMEN             --}}
{{-- ============================== --}}
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    {{-- Ingresos totales --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6 hover:shadow-lg hover:shadow-gray-100/50 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex items-center gap-1 text-xs font-medium text-green-600 bg-green-50 px-2.5 py-1 rounded-full">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
                +12%
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-900">$2.450.000</p>
        <p class="text-sm text-gray-500 mt-1">Ingresos del mes</p>
    </div>

    {{-- Reparaciones en proceso --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6 hover:shadow-lg hover:shadow-gray-100/50 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full">En proceso</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">8</p>
        <p class="text-sm text-gray-500 mt-1">Reparaciones activas</p>
    </div>

    {{-- Reparaciones completadas --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6 hover:shadow-lg hover:shadow-gray-100/50 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full">Completadas</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">23</p>
        <p class="text-sm text-gray-500 mt-1">Este mes</p>
    </div>

    {{-- Ganancia neta --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6 hover:shadow-lg hover:shadow-gray-100/50 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <div class="flex items-center gap-1 text-xs font-medium text-green-600 bg-green-50 px-2.5 py-1 rounded-full">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
                +8%
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-900">$1.680.000</p>
        <p class="text-sm text-gray-500 mt-1">Ganancia neta del mes</p>
    </div>

</div>

{{-- ============================== --}}
{{-- GRÁFICAS - Dos columnas         --}}
{{-- ============================== --}}
<div class="grid lg:grid-cols-3 gap-6 mb-8">

    {{-- Gráfica de ingresos por mes (2 columnas) --}}
    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="font-semibold text-gray-900">Ingresos mensuales</h3>
                <p class="text-sm text-gray-500 mt-0.5">Comparativo de ingresos por mes</p>
            </div>
            <div class="flex items-center gap-4 text-xs">
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                    <span class="text-gray-500">Reparaciones</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                    <span class="text-gray-500">Ventas</span>
                </div>
            </div>
        </div>
        {{-- Canvas para Chart.js --}}
        <div class="h-72">
            <canvas id="chart-ingresos"></canvas>
        </div>
    </div>

    {{-- Gráfica de reparaciones por estado (1 columna) --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <div class="mb-6">
            <h3 class="font-semibold text-gray-900">Por estado</h3>
            <p class="text-sm text-gray-500 mt-0.5">Distribución actual</p>
        </div>
        <div class="h-52 flex items-center justify-center">
            <canvas id="chart-estados"></canvas>
        </div>
        {{-- Leyenda manual debajo del doughnut --}}
        <div class="space-y-3 mt-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                    <span class="text-sm text-gray-600">En proceso</span>
                </div>
                <span class="text-sm font-semibold text-gray-900">8</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                    <span class="text-sm text-gray-600">Arreglados</span>
                </div>
                <span class="text-sm font-semibold text-gray-900">5</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                    <span class="text-sm text-gray-600">Entregados</span>
                </div>
                <span class="text-sm font-semibold text-gray-900">23</span>
            </div>
        </div>
    </div>

</div>

{{-- ============================== --}}
{{-- TABLA + CAJAS - Dos columnas    --}}
{{-- ============================== --}}
<div class="grid lg:grid-cols-3 gap-6">

    {{-- Últimas reparaciones (2 columnas) --}}
    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-semibold text-gray-900">Últimas reparaciones</h3>
            <a href="#" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Ver todas</a>
        </div>

        {{-- Tabla responsive --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Cliente</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Equipo</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Caja</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Valor</th>
                        <th class="text-left py-3 px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    {{-- Datos de ejemplo --}}
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="py-3 px-2">
                            <p class="font-medium text-gray-900">Juan Pérez</p>
                            <p class="text-xs text-gray-400">310 123 4567</p>
                        </td>
                        <td class="py-3 px-2">
                            <p class="text-gray-700">Samsung</p>
                            <p class="text-xs text-gray-400">Galaxy S23</p>
                        </td>
                        <td class="py-3 px-2">
                            <span class="bg-gray-100 text-gray-700 text-xs font-bold px-2.5 py-1 rounded-lg">A1</span>
                        </td>
                        <td class="py-3 px-2 font-medium text-gray-900">$150.000</td>
                        <td class="py-3 px-2">
                            <span class="bg-amber-50 text-amber-700 text-xs font-medium px-2.5 py-1 rounded-full">En proceso</span>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="py-3 px-2">
                            <p class="font-medium text-gray-900">María López</p>
                            <p class="text-xs text-gray-400">315 987 6543</p>
                        </td>
                        <td class="py-3 px-2">
                            <p class="text-gray-700">iPhone</p>
                            <p class="text-xs text-gray-400">14 Pro</p>
                        </td>
                        <td class="py-3 px-2">
                            <span class="bg-gray-100 text-gray-700 text-xs font-bold px-2.5 py-1 rounded-lg">B2</span>
                        </td>
                        <td class="py-3 px-2 font-medium text-gray-900">$280.000</td>
                        <td class="py-3 px-2">
                            <span class="bg-blue-50 text-blue-700 text-xs font-medium px-2.5 py-1 rounded-full">Arreglado</span>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="py-3 px-2">
                            <p class="font-medium text-gray-900">Carlos García</p>
                            <p class="text-xs text-gray-400">300 456 7890</p>
                        </td>
                        <td class="py-3 px-2">
                            <p class="text-gray-700">Xiaomi</p>
                            <p class="text-xs text-gray-400">Redmi Note 12</p>
                        </td>
                        <td class="py-3 px-2">
                            <span class="bg-gray-100 text-gray-700 text-xs font-bold px-2.5 py-1 rounded-lg">A3</span>
                        </td>
                        <td class="py-3 px-2 font-medium text-gray-900">$85.000</td>
                        <td class="py-3 px-2">
                            <span class="bg-green-50 text-green-700 text-xs font-medium px-2.5 py-1 rounded-full">Entregado</span>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="py-3 px-2">
                            <p class="font-medium text-gray-900">Ana Martínez</p>
                            <p class="text-xs text-gray-400">318 222 3344</p>
                        </td>
                        <td class="py-3 px-2">
                            <p class="text-gray-700">Motorola</p>
                            <p class="text-xs text-gray-400">Moto G54</p>
                        </td>
                        <td class="py-3 px-2">
                            <span class="bg-gray-100 text-gray-700 text-xs font-bold px-2.5 py-1 rounded-lg">C1</span>
                        </td>
                        <td class="py-3 px-2 font-medium text-gray-900">$60.000</td>
                        <td class="py-3 px-2">
                            <span class="bg-amber-50 text-amber-700 text-xs font-medium px-2.5 py-1 rounded-full">En proceso</span>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="py-3 px-2">
                            <p class="font-medium text-gray-900">Pedro Ruiz</p>
                            <p class="text-xs text-gray-400">301 555 8899</p>
                        </td>
                        <td class="py-3 px-2">
                            <p class="text-gray-700">Samsung</p>
                            <p class="text-xs text-gray-400">Galaxy A54</p>
                        </td>
                        <td class="py-3 px-2">
                            <span class="bg-gray-100 text-gray-700 text-xs font-bold px-2.5 py-1 rounded-lg">B4</span>
                        </td>
                        <td class="py-3 px-2 font-medium text-gray-900">$120.000</td>
                        <td class="py-3 px-2">
                            <span class="bg-green-50 text-green-700 text-xs font-medium px-2.5 py-1 rounded-full">Entregado</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Estado de cajas (1 columna) --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-semibold text-gray-900">Cajas</h3>
            <span class="text-xs text-gray-400">Tiempo real</span>
        </div>

        <div class="space-y-5">
            {{-- Grupo A --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Grupo A</p>
                <div class="flex flex-wrap gap-2">
                    {{-- A1 ocupada (ejemplo) --}}
                    <div class="w-12 h-12 rounded-xl bg-red-50 border border-red-200 flex items-center justify-center text-red-700 text-xs font-bold cursor-default" title="Ocupada - Juan Pérez">
                        A1
                    </div>
                    @for ($i = 2; $i <= 4; $i++)
                    <div class="w-12 h-12 rounded-xl bg-green-50 border border-green-200 flex items-center justify-center text-green-700 text-xs font-bold cursor-default" title="Libre">
                        A{{ $i }}
                    </div>
                    @endfor
                    {{-- A5 ocupada (ejemplo) --}}
                    <div class="w-12 h-12 rounded-xl bg-red-50 border border-red-200 flex items-center justify-center text-red-700 text-xs font-bold cursor-default" title="Ocupada - Ana Martínez">
                        A5
                    </div>
                </div>
            </div>

            {{-- Grupo B --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Grupo B</p>
                <div class="flex flex-wrap gap-2">
                    <div class="w-12 h-12 rounded-xl bg-green-50 border border-green-200 flex items-center justify-center text-green-700 text-xs font-bold cursor-default">B1</div>
                    <div class="w-12 h-12 rounded-xl bg-red-50 border border-red-200 flex items-center justify-center text-red-700 text-xs font-bold cursor-default" title="Ocupada - María López">B2</div>
                    <div class="w-12 h-12 rounded-xl bg-green-50 border border-green-200 flex items-center justify-center text-green-700 text-xs font-bold cursor-default">B3</div>
                    <div class="w-12 h-12 rounded-xl bg-red-50 border border-red-200 flex items-center justify-center text-red-700 text-xs font-bold cursor-default" title="Ocupada - Pedro Ruiz">B4</div>
                    <div class="w-12 h-12 rounded-xl bg-green-50 border border-green-200 flex items-center justify-center text-green-700 text-xs font-bold cursor-default">B5</div>
                </div>
            </div>

            {{-- Grupo C --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Grupo C</p>
                <div class="flex flex-wrap gap-2">
                    <div class="w-12 h-12 rounded-xl bg-red-50 border border-red-200 flex items-center justify-center text-red-700 text-xs font-bold cursor-default" title="Ocupada">C1</div>
                    @for ($i = 2; $i <= 5; $i++)
                    <div class="w-12 h-12 rounded-xl bg-green-50 border border-green-200 flex items-center justify-center text-green-700 text-xs font-bold cursor-default">C{{ $i }}</div>
                    @endfor
                </div>
            </div>
        </div>

        {{-- Leyenda --}}
        <div class="flex items-center gap-4 mt-6 pt-4 border-t border-gray-100">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded bg-green-200 border border-green-300"></div>
                <span class="text-xs text-gray-500">Libre (10)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded bg-red-200 border border-red-300"></div>
                <span class="text-xs text-gray-500">Ocupada (5)</span>
            </div>
        </div>
    </div>

</div>

@endsection

{{-- ============================== --}}
{{-- SCRIPTS - Chart.js              --}}
{{-- ============================== --}}
@section('scripts')
{{--
    Chart.js se carga desde CDN.
    Lo cargamos en la sección scripts para que no afecte
    otras vistas que no necesitan gráficas.
--}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
    // ── Gráfica de ingresos mensuales (barras) ──
    // Datos de ejemplo: ingresos por reparaciones y ventas por mes
    const ctxIngresos = document.getElementById('chart-ingresos').getContext('2d');
    new Chart(ctxIngresos, {
        type: 'bar',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            datasets: [
                {
                    label: 'Reparaciones',
                    data: [1800000, 2100000, 1950000, 2300000, 2450000, 2100000, 2600000, 2800000, 2400000, 2700000, 2900000, 2450000],
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    label: 'Ventas',
                    data: [650000, 800000, 720000, 900000, 850000, 780000, 950000, 1100000, 880000, 1000000, 1050000, 900000],
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderRadius: 6,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            // Interacción: al pasar el mouse muestra ambos datasets
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 13 },
                    bodyFont: { size: 12 },
                    padding: 12,
                    cornerRadius: 10,
                    // Formato de moneda colombiana en el tooltip
                    callbacks: {
                        label: function(ctx) {
                            return ctx.dataset.label + ': $' + ctx.parsed.y.toLocaleString('es-CO');
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 12 } }
                },
                y: {
                    grid: { color: '#f1f5f9' },
                    border: { display: false },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 11 },
                        // Formato corto: 1.8M, 2.1M
                        callback: function(value) {
                            return '$' + (value / 1000000).toFixed(1) + 'M';
                        }
                    }
                }
            }
        }
    });

    // ── Gráfica de estados (doughnut) ──
    const ctxEstados = document.getElementById('chart-estados').getContext('2d');
    new Chart(ctxEstados, {
        type: 'doughnut',
        data: {
            labels: ['En proceso', 'Arreglados', 'Entregados'],
            datasets: [{
                data: [8, 5, 23],
                backgroundColor: [
                    'rgba(251, 191, 36, 0.9)',
                    'rgba(59, 130, 246, 0.9)',
                    'rgba(16, 185, 129, 0.9)',
                ],
                borderWidth: 0,
                spacing: 4,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    cornerRadius: 10,
                }
            }
        }
    });

    // ── Función placeholder para filtros ──
    // Cuando conectemos datos reales, esta función hará
    // una petición AJAX al servidor para actualizar las gráficas
    function updateFilter(range) {
        console.log('Filtro seleccionado:', range);
        // TODO: fetch('/admin/dashboard/data?range=' + range)
    }
</script>
@endsection
