{{--
    Vista principal de Reparaciones.

    Versión simplificada: nombre y teléfono directo en el formulario.
    Sin buscador de clientes.
--}}
@extends('layouts.admin')

@section('page-title', 'Reparaciones')

@section('content')

{{-- Mensajes flash --}}
@if (session('success'))
    <div id="alert-success" class="mb-6 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl px-4 py-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div id="alert-error" class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('error') }}
    </div>
@endif

{{-- Header + Botón --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Reparaciones</h2>
        <p class="text-gray-500 mt-1">Gestiona las reparaciones del local</p>
    </div>
    <button onclick="document.getElementById('modal-nueva').classList.remove('hidden')"
            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-5 py-3 rounded-xl transition shadow-sm shadow-blue-600/20 ">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva reparación
    </button>
</div>

{{-- Tarjetas de resumen — totales reales por estado (no filtrados) --}}
<div class="grid sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 p-4 flex items-center gap-4">
        <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-900">{{ $contadores['en_proceso'] }}</p>
            <p class="text-xs text-gray-500">En proceso</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 flex items-center gap-4">
        <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-900">{{ $contadores['arreglado'] }}</p>
            <p class="text-xs text-gray-500">Arreglados</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 flex items-center gap-4">
        <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-900">{{ $contadores['entregado'] }}</p>
            <p class="text-xs text-gray-500">Entregados</p>
        </div>
    </div>
</div>

{{-- ============================== --}}
{{-- FILTROS — form GET unificado   --}}
{{-- ============================== --}}
@php
    $fEstado  = request('estado');
    $fPeriodo = request('periodo', 'mes');
    $fBuscar  = request('buscar');
    $fDesde   = request('desde');
    $fHasta   = request('hasta');

    $sinFiltroFecha  = in_array($fEstado, ['en_proceso', 'arreglado']);
    $hayFiltrosExtra = $fBuscar
        || ($fEstado && $fEstado !== 'todos')
        || (!$sinFiltroFecha && $fPeriodo && $fPeriodo !== 'mes')
        || $fDesde || $fHasta;
@endphp

<form method="GET" action="{{ route('admin.reparaciones.index') }}" id="form-filtros">
    <div class="flex flex-wrap gap-3 mb-3">

        {{-- Select Estado --}}
        <select name="estado" id="select-estado"
                class="px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer">
            <option value="todos"      {{ (!$fEstado || $fEstado === 'todos')   ? 'selected' : '' }}>Todos</option>
            <option value="en_proceso" {{ $fEstado === 'en_proceso'             ? 'selected' : '' }}>En proceso</option>
            <option value="arreglado"  {{ $fEstado === 'arreglado'              ? 'selected' : '' }}>Arreglados</option>
            <option value="entregado"  {{ $fEstado === 'entregado'              ? 'selected' : '' }}>Entregados</option>
        </select>

        {{-- Select Periodo (oculto para en_proceso y arreglado) --}}
        <div id="periodo-container" class="{{ $sinFiltroFecha ? 'hidden' : '' }}">
            <select name="periodo" id="select-periodo"
                    class="h-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer">
                <option value="hoy"          {{ $fPeriodo === 'hoy'          ? 'selected' : '' }}>Hoy</option>
                <option value="semana"        {{ $fPeriodo === 'semana'        ? 'selected' : '' }}>Esta semana</option>
                <option value="mes"           {{ (!$fPeriodo || $fPeriodo === 'mes') ? 'selected' : '' }}>Este mes</option>
                <option value="mes_pasado"    {{ $fPeriodo === 'mes_pasado'    ? 'selected' : '' }}>Mes pasado</option>
                <option value="trimestre"     {{ $fPeriodo === 'trimestre'     ? 'selected' : '' }}>Últimos 3 meses</option>
                <option value="anio"          {{ $fPeriodo === 'anio'          ? 'selected' : '' }}>Este año</option>
                <option value="personalizado" {{ $fPeriodo === 'personalizado' ? 'selected' : '' }}>Personalizado</option>
            </select>
        </div>

        {{-- Campos desde/hasta (solo para periodo=personalizado) --}}
        <div id="fechas-personalizado"
             class="{{ $fPeriodo === 'personalizado' && !$sinFiltroFecha ? 'flex gap-2' : 'hidden' }}">
            <input type="date" name="desde" value="{{ $fDesde }}"
                   class="px-3 py-3 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
            <input type="date" name="hasta" value="{{ $fHasta }}"
                   class="px-3 py-3 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
        </div>

        {{-- Buscador --}}
        <div class="flex flex-1 min-w-56 gap-2">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="buscar" id="input-buscar" value="{{ $fBuscar }}"
                       placeholder="Buscar por cliente, teléfono, marca..."
                       class="w-full pl-12 pr-4 py-3 bg-white border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
            </div>

            {{-- Botón buscar --}}
            <button type="submit"
                    class="px-4 py-3 bg-white border border-gray-200 rounded-xl text-gray-500 hover:bg-gray-50 hover:text-blue-600 transition"
                    title="Buscar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>

            {{-- Botón limpiar búsqueda (X) --}}
            @if($fBuscar)
                <button type="button" onclick="limpiarBusqueda()"
                        class="px-4 py-3 bg-white border border-gray-200 rounded-xl text-gray-500 hover:bg-red-50 hover:text-red-500 transition"
                        title="Limpiar búsqueda">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            @endif
        </div>

        {{-- Botón limpiar todos los filtros --}}
        @if($hayFiltrosExtra)
            <a href="{{ route('admin.reparaciones.index') }}"
               class="inline-flex items-center gap-2 px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm text-gray-500 hover:bg-red-50 hover:text-red-500 hover:border-red-200 transition whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Limpiar filtros
            </a>
        @endif

    </div>
</form>

{{-- Indicador de filtros activos --}}
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">
        @php
            $estadoLabel = match($fEstado) {
                'en_proceso' => 'en proceso',
                'arreglado'  => 'arregladas',
                'entregado'  => 'entregadas',
                default      => null,
            };

            $periodoLabel = match($fPeriodo) {
                'hoy'          => 'de hoy',
                'semana'       => 'de esta semana',
                'mes_pasado'   => 'del mes pasado',
                'trimestre'    => 'de los últimos 3 meses',
                'anio'         => 'de este año',
                'personalizado'=> ($fDesde && $fHasta)
                    ? 'del ' . \Carbon\Carbon::parse($fDesde)->format('d/m/Y') . ' al ' . \Carbon\Carbon::parse($fHasta)->format('d/m/Y')
                    : 'de este mes',
                default        => 'de este mes',
            };
        @endphp

        @if($fBuscar && $estadoLabel)
            <strong>{{ $reparaciones->count() }}</strong> resultado{{ $reparaciones->count() !== 1 ? 's' : '' }}
            para "<strong>{{ $fBuscar }}</strong>" en {{ $estadoLabel }}
        @elseif($fBuscar)
            <strong>{{ $reparaciones->count() }}</strong> resultado{{ $reparaciones->count() !== 1 ? 's' : '' }}
            para "<strong>{{ $fBuscar }}</strong>"
        @elseif($estadoLabel && $sinFiltroFecha)
            Mostrando reparaciones {{ $estadoLabel }}
        @elseif($estadoLabel)
            Mostrando reparaciones {{ $estadoLabel }} {{ $periodoLabel }}
        @else
            Mostrando todas las reparaciones {{ $periodoLabel }}
        @endif
    </p>
</div>

{{-- Tabla --}}
<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">

    @if($reparaciones->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">ID</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Fecha</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Cliente</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Equipo</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Caja</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Valor</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Abonado</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Saldo</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Estado</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($reparaciones as $rep)
                <tr class="hover:bg-gray-50/50 transition reparacion-row"
    data-id="{{ $rep->id }}"
    data-estado="{{ $rep->estado->value }}"
    data-edit="{{ json_encode([
    'id'                => $rep->id,
    'nombre_cliente'    => $rep->nombre_cliente,
    'telefono_cliente'  => $rep->telefono_cliente ?? '',
    'marca'             => $rep->marca,
    'modelo'            => $rep->modelo ?? '',
    'descripcion_falla' => $rep->descripcion_falla ?? '',
    'valor_total'       => (int) $rep->valor_total,
    'costo_repuestos'   => (int) $rep->costo_repuestos,
    'abonos'            => $rep->abonos->map(fn($a) => [
        'monto'      => (int) $a->monto,
        'nota'       => $a->nota ?? '',
        'fecha'      => $a->created_at->format('d/m/Y h:i A'),
    ])->toArray(),
]) }}">

                    <td class="py-3 px-4 text-gray-400 text-xs">#{{ $rep->id }}</td>

                    <td class="py-3 px-4 whitespace-nowrap">
                        <p class="text-gray-700 text-xs">{{ $rep->created_at->format('d/m/Y') }}</p>
                        <p class="text-gray-400 text-xs">{{ $rep->created_at->format('h:i A') }}</p>
                    </td>

                    <td class="py-3 px-4" data-celda="cliente">
    <p class="font-medium text-gray-900">{{ $rep->nombre_cliente }}</p>
    <p class="text-xs text-gray-400">{{ $rep->telefono_cliente ?? 'Sin teléfono' }}</p>
</td>

                    <td class="py-3 px-4" data-celda="equipo">
    <p class="text-gray-700">{{ $rep->marca }}</p>
    <p class="text-xs text-gray-400">{{ $rep->modelo ?? '—' }}</p>
</td>

                    <td class="py-3 px-4">
                        <span class="bg-gray-100 text-gray-700 text-xs font-bold px-2.5 py-1 rounded-lg">
                            {{ $rep->caja->nombre_display }}
                        </span>
                    </td>

                    <td class="py-3 px-4 font-medium text-gray-900" data-celda="valor">
    ${{ number_format($rep->valor_total, 0, ',', '.') }}
</td>
                    <td class="py-3 px-4 text-gray-700" data-celda="abonado">
    ${{ number_format($rep->total_abonado, 0, ',', '.') }}
</td>

                    <td class="py-3 px-4" data-celda="saldo">
    @php $saldo = $rep->saldo_pendiente; @endphp
                        <span class="{{ $saldo > 0 ? 'text-red-600' : 'text-green-600' }} font-medium">
                            ${{ number_format($saldo, 0, ',', '.') }}
                        </span>
                    </td>

                    <td class="py-3 px-4">
                        <select onchange="cambiarEstado(this, {{ $rep->id }})"
                                class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach(App\Enums\EstadoReparacion::cases() as $estado)
                                <option value="{{ $estado->value }}" {{ $rep->estado === $estado ? 'selected' : '' }}>
                                    {{ $estado->label() }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    <td class="py-3 px-4">
                        <div class="flex items-center gap-2">
                            {{-- Botón editar --}}
                            <button onclick="abrirModalEditar(this)"
        class="text-gray-400 hover:text-blue-600 transition"
        title="Editar reparación">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            {{-- Botón registrar abono --}}
                            <button onclick="abrirModalAbono({{ $rep->id }}, '{{ $rep->nombre_cliente }}', {{ $rep->saldo_pendiente }})"
                                    class="text-blue-600 hover:text-blue-800 transition"
                                    title="Registrar abono">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.42 15.17l-5.1-5.1a2.5 2.5 0 010-3.54l.71-.7a2.5 2.5 0 013.54 0l5.1 5.1m-4.25 4.24l4.24-4.24m0 0l5.1 5.1a2.5 2.5 0 010 3.54l-.7.7a2.5 2.5 0 01-3.55 0l-5.1-5.1"/>
            </svg>
        </div>
        <p class="text-gray-400 text-sm">No hay reparaciones registradas</p>
        <p class="text-gray-300 text-xs mt-1">Crea la primera con el botón de arriba</p>
    </div>
    @endif
</div>
{{-- Paginación (solo cuando hay más de una página y no hay búsqueda por texto) --}}
@if(!request('buscar') && method_exists($reparaciones, 'hasPages') && $reparaciones->hasPages())
<div class="mt-6 flex justify-center">
    {{ $reparaciones->appends(request()->query())->links() }}
</div>
@endif

{{-- ============================== --}}
{{-- MODAL - Nueva Reparación        --}}
{{-- ============================== --}}
<div id="modal-nueva" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('modal-nueva').classList.add('hidden')"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

        <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 rounded-t-2xl flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Nueva reparación</h3>
                <p class="text-sm text-gray-500">Registra un equipo para reparar</p>
            </div>
            <button onclick="document.getElementById('modal-nueva').classList.add('hidden')"
                    class="p-2 rounded-xl hover:bg-gray-100 transition">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.reparaciones.store') }}" class="p-6 space-y-5">
            @csrf

            {{-- Cliente --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-3">Cliente</label>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Nombre *</label>
                        <input type="text" name="nombre_cliente" required placeholder="Juan Pérez"
                               value="{{ old('nombre_cliente') }}"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Teléfono</label>
                        <input type="text" name="telefono_cliente" placeholder="310 123 4567"
                               value="{{ old('telefono_cliente') }}"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                </div>
            </div>

            {{-- Equipo --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Marca *</label>
                    <input type="text" name="marca" required placeholder="Samsung, iPhone..."
                           value="{{ old('marca') }}"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Modelo</label>
                    <input type="text" name="modelo" placeholder="Galaxy S24..."
                           value="{{ old('modelo') }}"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
            </div>

            {{-- Caja --}}
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Caja *</label>
                <select name="caja_id" required
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    <option value="">Seleccionar caja...</option>
                    @foreach($cajasLibres as $caja)
                        <option value="{{ $caja->id }}" {{ old('caja_id') == $caja->id ? 'selected' : '' }}>
                            {{ $caja->nombre_display }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Falla --}}
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Descripción de la falla</label>
                <textarea name="descripcion_falla" rows="2" placeholder="¿Qué le pasa al equipo?"
                          class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition resize-none">{{ old('descripcion_falla') }}</textarea>
            </div>

            {{-- Valores --}}
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Valor total *</label>
                    <input type="text" id="valor_total_visual" placeholder="150.000" inputmode="numeric"
       oninput="formatearPrecio(this, 'valor_total')"
       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
<input type="hidden" name="valor_total" id="valor_total" value="{{ old('valor_total') }}">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Repuestos</label>
                    <input type="text" id="costo_repuestos_visual" placeholder="50.000" inputmode="numeric"
       oninput="formatearPrecio(this, 'costo_repuestos')"
       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
<input type="hidden" name="costo_repuestos" id="costo_repuestos" value="{{ old('costo_repuestos') }}">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Abono inicial</label>
                    <input type="text" id="abono_inicial_visual" placeholder="50.000" inputmode="numeric"
       oninput="formatearPrecio(this, 'abono_inicial')"
       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
<input type="hidden" name="abono_inicial" id="abono_inicial" value="{{ old('abono_inicial') }}">
                </div>
            </div>

            {{-- Errores --}}
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Botones --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('modal-nueva').classList.add('hidden')"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-sm shadow-blue-600/20">
                    Guardar reparación
                </button>
            </div>
        </form>
    </div>
</div>


{{-- ============================== --}}
{{-- MODAL - Editar Reparación       --}}
{{-- ============================== --}}
<div id="modal-editar" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="cerrarModalEditar()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

        <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 rounded-t-2xl flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Editar reparación</h3>
                <p class="text-sm text-gray-500" id="edit-subtitulo"></p>
            </div>
            <button onclick="cerrarModalEditar()" class="p-2 rounded-xl hover:bg-gray-100 transition">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="p-6 space-y-5">

            {{-- Errores --}}
            <div id="edit-errores" class="hidden bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
                <p class="font-semibold mb-1">Por favor corrige los siguientes errores:</p>
                <ul id="edit-errores-lista" class="list-disc list-inside space-y-0.5"></ul>
            </div>

            {{-- Cliente --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Cliente</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Nombre *</label>
                        <input type="text" id="edit-nombre_cliente" placeholder="Juan Pérez"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Teléfono</label>
                        <input type="text" id="edit-telefono_cliente" placeholder="310 123 4567"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                </div>
            </div>

            {{-- Equipo --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Equipo</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Marca *</label>
                        <input type="text" id="edit-marca" placeholder="Samsung, iPhone..."
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Modelo</label>
                        <input type="text" id="edit-modelo" placeholder="Galaxy S24..."
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="text-xs text-gray-500 mb-1 block">Descripción de la falla</label>
                    <textarea id="edit-descripcion_falla" rows="2" placeholder="¿Qué le pasa al equipo?"
                              class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition resize-none"></textarea>
                </div>
            </div>

            {{-- Valores --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Valores</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Valor total *</label>
                        <input type="text" id="edit-valor_total_visual" placeholder="150.000" inputmode="numeric"
                               oninput="formatearPrecio(this, 'edit-valor_total')"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        <input type="hidden" id="edit-valor_total">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Costo repuestos</label>
                        <input type="text" id="edit-costo_repuestos_visual" placeholder="50.000" inputmode="numeric"
                               oninput="formatearPrecio(this, 'edit-costo_repuestos')"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        <input type="hidden" id="edit-costo_repuestos">
                    </div>
                </div>
            </div>

            {{-- Abonos --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Abonos registrados</p>
                <div id="edit-abonos-lista" class="space-y-2">
                    {{-- Se llena dinámicamente con JS --}}
                </div>
                <p id="edit-abonos-vacio" class="hidden text-xs text-gray-400 italic">
                    Esta reparación no tiene abonos aún.
                </p>
            </div>

            {{-- Botones --}}
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <button type="button" onclick="cerrarModalEditar()"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                    Cancelar
                </button>
                <button type="button" onclick="guardarEdicion()" id="edit-btn-guardar"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-sm shadow-blue-600/20">
                    Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================== --}}
{{-- MODAL - Registrar Abono         --}}
{{-- ============================== --}}
<div id="modal-abono" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('modal-abono').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Registrar abono</h3>
                <p class="text-sm text-gray-500" id="abono-cliente-nombre"></p>
            </div>
            <button onclick="document.getElementById('modal-abono').classList.add('hidden')"
                    class="p-2 rounded-xl hover:bg-gray-100 transition">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="abono-reparacion-id">
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Saldo pendiente</label>
                <p class="text-2xl font-bold text-red-600" id="abono-saldo-display"></p>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Monto a abonar *</label>
                <input type="text" id="abono-monto-visual" placeholder="50.000" inputmode="numeric"
       oninput="formatearPrecio(this, 'abono-monto')"
       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
<input type="hidden" id="abono-monto">
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Nota (opcional)</label>
                <input type="text" id="abono-nota" placeholder="Ej: Pagó la mitad"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('modal-abono').classList.add('hidden')"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                    Cancelar
                </button>
                <button type="button" onclick="guardarAbono()"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-sm shadow-blue-600/20">
                    Registrar abono
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;


    // ══════════════════════════════════════
// MODAL EDITAR
// ══════════════════════════════════════
let editReparacionId = null;
let editFila = null;

function abrirModalEditar(btn) {
    const fila  = btn.closest('tr');
    const datos = JSON.parse(fila.dataset.edit);

    editReparacionId = datos.id;
    editFila = fila;

    document.getElementById('edit-subtitulo').textContent = `Reparación #${datos.id}`;
    document.getElementById('edit-nombre_cliente').value    = datos.nombre_cliente;
    document.getElementById('edit-telefono_cliente').value  = datos.telefono_cliente;
    document.getElementById('edit-marca').value             = datos.marca;
    document.getElementById('edit-modelo').value            = datos.modelo;
    document.getElementById('edit-descripcion_falla').value = datos.descripcion_falla;

    document.getElementById('edit-valor_total').value        = datos.valor_total;
    document.getElementById('edit-valor_total_visual').value = datos.valor_total
        ? datos.valor_total.toLocaleString('es-CO') : '';

    document.getElementById('edit-costo_repuestos').value        = datos.costo_repuestos;
    document.getElementById('edit-costo_repuestos_visual').value = datos.costo_repuestos
        ? datos.costo_repuestos.toLocaleString('es-CO') : '';

    // ── Abonos ──
    const lista  = document.getElementById('edit-abonos-lista');
    const vacio  = document.getElementById('edit-abonos-vacio');
    lista.innerHTML = '';

    if (datos.abonos && datos.abonos.length > 0) {
        vacio.classList.add('hidden');
        datos.abonos.forEach(abono => {
            const item = document.createElement('div');
            item.className = 'flex items-start justify-between gap-3 bg-gray-50 rounded-xl px-4 py-3';
            item.innerHTML = `
                <div>
                    <p class="text-xs text-gray-400">${abono.fecha}</p>
                    ${abono.nota
                        ? `<p class="text-xs text-gray-500 mt-0.5 italic">${abono.nota}</p>`
                        : ''}
                </div>
                <span class="text-sm font-semibold text-green-600 whitespace-nowrap">
                    $${abono.monto.toLocaleString('es-CO')}
                </span>`;
            lista.appendChild(item);
        });
    } else {
        vacio.classList.remove('hidden');
    }

    document.getElementById('edit-errores').classList.add('hidden');
    document.getElementById('edit-errores-lista').innerHTML = '';
    document.getElementById('modal-editar').classList.remove('hidden');
}

function cerrarModalEditar() {
    document.getElementById('modal-editar').classList.add('hidden');
    editReparacionId = null;
    editFila = null;
}

function guardarEdicion() {
    const btn    = document.getElementById('edit-btn-guardar');
    const nombre = document.getElementById('edit-nombre_cliente').value.trim();
    const marca  = document.getElementById('edit-marca').value.trim();
    const valor  = document.getElementById('edit-valor_total').value;

    if (!nombre || !marca || !valor) {
        document.getElementById('edit-errores-lista').innerHTML = '<li>Nombre, marca y valor total son obligatorios.</li>';
        document.getElementById('edit-errores').classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const payload = {
        _method:           'PUT',
        nombre_cliente:    nombre,
        telefono_cliente:  document.getElementById('edit-telefono_cliente').value.trim(),
        marca:             marca,
        modelo:            document.getElementById('edit-modelo').value.trim(),
        descripcion_falla: document.getElementById('edit-descripcion_falla').value.trim(),
        valor_total:       valor,
        costo_repuestos:   document.getElementById('edit-costo_repuestos').value || null,
    };

    fetch(`/admin/reparaciones/${editReparacionId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'X-HTTP-Method-Override': 'PUT',
        },
        body: JSON.stringify(payload),
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.textContent = 'Guardar cambios';

        if (data.success) {
            cerrarModalEditar();
            actualizarFilaEdicion(data.data);
            mostrarNotificacion(data.message);
        } else {
            const errores = data.errors
                ? Object.values(data.errors).flat()
                : [data.message];
            document.getElementById('edit-errores-lista').innerHTML = errores.map(e => `<li>${e}</li>`).join('');
            document.getElementById('edit-errores').classList.remove('hidden');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = 'Guardar cambios';
        mostrarNotificacion('Error de conexión', 'error');
    });
}

function actualizarFilaEdicion(datos) {
    if (!editFila) return;

    editFila.querySelector('[data-celda="cliente"]').innerHTML = `
        <p class="font-medium text-gray-900">${datos.nombre_cliente}</p>
        <p class="text-xs text-gray-400">${datos.telefono_cliente || 'Sin teléfono'}</p>`;

    editFila.querySelector('[data-celda="equipo"]').innerHTML = `
        <p class="text-gray-700">${datos.marca}</p>
        <p class="text-xs text-gray-400">${datos.modelo || '—'}</p>`;

    editFila.querySelector('[data-celda="valor"]').textContent = '$' + datos.valor_total.toLocaleString('es-CO');

    const abonado = parseInt(editFila.querySelector('[data-celda="abonado"]').textContent.replace(/\D/g, '')) || 0;
    const saldo   = datos.valor_total - abonado;
    editFila.querySelector('[data-celda="saldo"]').innerHTML = `
        <span class="${saldo > 0 ? 'text-red-600' : 'text-green-600'} font-medium">
            $${saldo.toLocaleString('es-CO')}
        </span>`;

    // Actualizar data-edit con valores frescos para una segunda edición
    editFila.dataset.edit = JSON.stringify({
        ...JSON.parse(editFila.dataset.edit),
        ...datos,
    });
}


// ══════════════════════════════════════
    // CAMBIAR ESTADO (AJAX sin recargar)
    // ══════════════════════════════════════
    function cambiarEstado(select, reparacionId) {
        const nuevoEstado = select.value;
        const fila = select.closest('tr');
        const estadoAnterior = fila.dataset.estado;

        // Feedback visual inmediato: borde verde en el select
        select.classList.add('ring-2', 'ring-green-400');

        fetch(`/admin/reparaciones/${reparacionId}/estado`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ estado: nuevoEstado })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Actualizar el data-estado de la fila (para que los filtros funcionen)
                fila.dataset.estado = nuevoEstado;

                // Actualizar contadores sin recargar
                actualizarContadores(estadoAnterior, nuevoEstado);

                // Mostrar notificación flotante
                mostrarNotificacion('Estado actualizado correctamente');

                // ── Animación de desaparición si hay filtro de estado activo ──
                //
                // Leemos el parámetro "estado" de la URL actual.
                // Si el usuario está filtrado (ej: ?estado=en_proceso) y acaba de
                // cambiar la fila a otro estado, esa fila ya no pertenece al filtro
                // activo → la animamos para que desaparezca sin recargar la página.
                const filtroActivo = new URLSearchParams(window.location.search).get('estado');

                // Solo actuamos si hay un filtro específico Y el nuevo estado es diferente.
                // Si no hay filtro (null) o es "todos", dejamos la fila como está.
                const debeDesaparecer = filtroActivo
                    && filtroActivo !== 'todos'
                    && filtroActivo !== nuevoEstado;

                if (debeDesaparecer) {
                    // Fase 1 — fundido: opacity 0 en 300ms
                    // Usamos style.transition + style.opacity para no depender de clases Tailwind
                    // que podrían no tener la propiedad transition configurada en este elemento.
                    fila.style.transition = 'opacity 0.3s ease';
                    fila.style.opacity    = '0';

                    // Fase 2 — colapso de altura: después de que termine el fundido (300ms)
                    // colapsamos el alto de la fila para que las filas de abajo suban suavemente
                    // en lugar de dejar un hueco vacío en la tabla.
                    setTimeout(() => {
                        // Guardamos el alto actual para poder animarlo desde ese valor hasta 0
                        fila.style.height     = fila.offsetHeight + 'px';
                        fila.style.overflow   = 'hidden';

                        // Forzamos un reflow para que el navegador registre el height fijo
                        // antes de cambiarlo; sin esto la transición no se dispara.
                        fila.offsetHeight; // eslint-disable-line no-unused-expressions

                        fila.style.transition = 'height 0.3s ease, padding 0.3s ease';
                        fila.style.height     = '0';
                        fila.style.paddingTop = '0';
                        fila.style.paddingBottom = '0';
                    }, 300);

                    // Fase 3 — eliminación del DOM: cuando terminaron ambas animaciones (600ms)
                    // removemos el nodo del DOM para que no ocupe espacio ni quede accesible.
                    setTimeout(() => {
                        fila.remove();
                    }, 620); // 20ms de margen extra sobre los 600ms para evitar flashes

                } else {
                    // Sin filtro activo o el estado coincide con el filtro:
                    // solo quitamos el anillo de feedback visual después de 1 segundo
                    setTimeout(() => {
                        select.classList.remove('ring-2', 'ring-green-400');
                    }, 1000);
                }

            } else {
                // Si falló, revertir el select al valor anterior
                select.value = estadoAnterior;
                mostrarNotificacion('Error: ' + data.message, 'error');
            }
        })
        .catch(() => {
            select.value = estadoAnterior;
            mostrarNotificacion('Error de conexión', 'error');
        });
    }

    /**
     * Actualiza los números de las tarjetas de resumen.
     * Resta 1 al estado anterior y suma 1 al nuevo.
     */
    function actualizarContadores(estadoAnterior, nuevoEstado) {
        const mapa = {
            'en_proceso': 0,
            'arreglado': 1,
            'entregado': 2,
        };

        const tarjetas = document.querySelectorAll('.grid.sm\\:grid-cols-3 .text-2xl');

        // Restar 1 al contador anterior
        if (mapa[estadoAnterior] !== undefined) {
            const valorAnterior = parseInt(tarjetas[mapa[estadoAnterior]].textContent);
            tarjetas[mapa[estadoAnterior]].textContent = Math.max(0, valorAnterior - 1);
        }

        // Sumar 1 al contador nuevo
        if (mapa[nuevoEstado] !== undefined) {
            const valorNuevo = parseInt(tarjetas[mapa[nuevoEstado]].textContent);
            tarjetas[mapa[nuevoEstado]].textContent = valorNuevo + 1;
        }
    }

    /**
     * Muestra una notificación flotante que desaparece sola.
     * Tipo: 'success' (verde) o 'error' (rojo)
     */
    function mostrarNotificacion(mensaje, tipo = 'success') {
        // Crear el elemento
        const noti = document.createElement('div');
        noti.className = `fixed top-6 right-6 z-50 flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-sm font-medium transition-all duration-300 transform translate-x-full ${
            tipo === 'success'
                ? 'bg-green-50 border border-green-200 text-green-700'
                : 'bg-red-50 border border-red-200 text-red-700'
        }`;

        // Ícono + mensaje
        noti.innerHTML = tipo === 'success'
            ? `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
               </svg>${mensaje}`
            : `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
               </svg>${mensaje}`;

        document.body.appendChild(noti);

        // Animar entrada (deslizar desde la derecha)
        requestAnimationFrame(() => {
            noti.classList.remove('translate-x-full');
        });

        // Desaparecer después de 3 segundos
        setTimeout(() => {
            noti.classList.add('translate-x-full');
            setTimeout(() => noti.remove(), 300);
        }, 3000);
    }

    // ══════════════════════════════════════
    // MODAL DE ABONOS
    // ══════════════════════════════════════
    function abrirModalAbono(reparacionId, clienteNombre, saldo) {
    document.getElementById('abono-reparacion-id').value        = reparacionId;
    document.getElementById('abono-cliente-nombre').textContent = clienteNombre;
    document.getElementById('abono-saldo-display').textContent  = '$' + saldo.toLocaleString('es-CO');
    document.getElementById('abono-monto').value                = '';
    document.getElementById('abono-monto-visual').value         = '';
    document.getElementById('abono-nota').value                 = '';

    const btn = document.querySelector('#modal-abono button[onclick="guardarAbono()"]');

    if (saldo <= 0) {
        btn.disabled = true;
        btn.textContent = 'Reparación ya pagada';
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        btn.classList.remove('hover:bg-blue-700');
    } else {
        btn.disabled = false;
        btn.textContent = 'Registrar abono';
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
        btn.classList.add('hover:bg-blue-700');
    }

    document.getElementById('modal-abono').classList.remove('hidden');
}

    function guardarAbono() {
    const reparacionId = document.getElementById('abono-reparacion-id').value;
    const monto        = document.getElementById('abono-monto').value;
    const nota         = document.getElementById('abono-nota').value;

    if (!monto || monto <= 0) {
        mostrarNotificacion('Ingresa un monto válido', 'error');
        return;
    }

    fetch(`/admin/reparaciones/${reparacionId}/abono`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ monto, nota })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('modal-abono').classList.add('hidden');

            const fila = document.querySelector(`tr[data-id="${reparacionId}"]`);
            if (fila) {
                const celdaAbonado = fila.querySelector('[data-celda="abonado"]');
                const celdaValor   = fila.querySelector('[data-celda="valor"]');
                const celdaSaldo   = fila.querySelector('[data-celda="saldo"]');

                const abonadoActual = parseInt(celdaAbonado.textContent.replace(/\D/g, '')) || 0;
                const valorTotal    = parseInt(celdaValor.textContent.replace(/\D/g, '')) || 0;
                const nuevoAbonado  = abonadoActual + parseInt(monto);
                const nuevoSaldo    = valorTotal - nuevoAbonado;

                celdaAbonado.textContent = '$' + nuevoAbonado.toLocaleString('es-CO');
                celdaSaldo.innerHTML = `<span class="${nuevoSaldo > 0 ? 'text-red-600' : 'text-green-600'} font-medium">$${nuevoSaldo.toLocaleString('es-CO')}</span>`;
            }

            mostrarNotificacion('Abono registrado correctamente');
        } else {
            mostrarNotificacion('Error: ' + data.message, 'error');
        }
    })
    .catch(() => mostrarNotificacion('Error de conexión', 'error'));
}

    // ══════════════════════════════════════
    // FILTROS
    // ══════════════════════════════════════

    const formFiltros    = document.getElementById('form-filtros');
    const selectEstado   = document.getElementById('select-estado');
    const selectPeriodo  = document.getElementById('select-periodo');
    const periodoContainer     = document.getElementById('periodo-container');
    const fechasPersonalizado  = document.getElementById('fechas-personalizado');

    /**
     * Al cambiar el estado:
     * - Si es en_proceso o arreglado → ocultar el select de periodo y enviar el form
     * - Para los demás → mostrar periodo y enviar el form
     */
    selectEstado.addEventListener('change', function () {
        const sinFecha = ['en_proceso', 'arreglado'].includes(this.value);
        periodoContainer.classList.toggle('hidden', sinFecha);
        if (sinFecha) fechasPersonalizado.classList.add('hidden');
        formFiltros.submit();
    });

    /**
     * Al cambiar el periodo:
     * - Si es "personalizado" → mostrar campos desde/hasta y NO enviar (esperar fechas)
     * - Para cualquier otro → ocultar fechas y enviar el form
     */
    if (selectPeriodo) {
        selectPeriodo.addEventListener('change', function () {
            if (this.value === 'personalizado') {
                fechasPersonalizado.classList.remove('hidden');
                fechasPersonalizado.classList.add('flex');
            } else {
                fechasPersonalizado.classList.add('hidden');
                fechasPersonalizado.classList.remove('flex');
                formFiltros.submit();
            }
        });
    }

    /**
     * Limpia el campo de búsqueda y envía el form (mantiene otros filtros).
     */
    function limpiarBusqueda() {
        document.getElementById('input-buscar').value = '';
        formFiltros.submit();
    }

    // ══════════════════════════════════════
    // AUTO-OCULTAR ALERTAS
    // ══════════════════════════════════════
    setTimeout(() => {
        document.querySelectorAll('[id^="alert-"]').forEach(el => {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        });
    }, 4000);

    // ══════════════════════════════════════
    // FORMATO DE MONEDA COLOMBIANA
    // ══════════════════════════════════════

    /**
     * Formatea un input con puntos de miles al estilo colombiano.
     *
     * El usuario ve: 150.000
     * El campo oculto (hidden) guarda: 150000
     * El servidor recibe el número limpio para guardarlo en la BD.
     *
     * inputmode="numeric" hace que en móvil aparezca el teclado numérico.
     */
    function formatearPrecio(input, hiddenId) {
        // Quitar todo lo que no sea número
        let valor = input.value.replace(/\D/g, '');

        // Guardar el valor limpio en el campo oculto (lo que se envía al servidor)
        document.getElementById(hiddenId).value = valor;

        // Formatear con puntos de miles para mostrar al usuario
        if (valor) {
            input.value = parseInt(valor).toLocaleString('es-CO');
        }
    }

    // Si hubo errores de validación, abrir el modal
    @if ($errors->any())
        document.getElementById('modal-nueva').classList.remove('hidden');
    @endif
</script>
@endsection
