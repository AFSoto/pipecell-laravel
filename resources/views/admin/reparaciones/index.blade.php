{{--
    Vista principal de Reparaciones.

    Versión simplificada: nombre y teléfono directo en el formulario.
    Sin buscador de clientes.
--}}
@extends('layouts.admin')

@section('page-title', 'Reparaciones')

{{-- Esta página escucha los avisos en tiempo real: el layout muestra el indicador
     "En vivo" y resources/js/tiempo-real.js se conecta a Pusher --}}
@section('tiempo-real', '1')

@section('content')

{{-- Header + Botón --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Reparaciones</h2>
        <p class="text-gray-500 mt-1">Gestiona las reparaciones del local</p>
    </div>
    <div class="flex items-center gap-3">
        <button onclick="document.getElementById('modal-nueva-caja').classList.remove('hidden')"
                class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm px-5 py-3 rounded-xl transition shadow-sm shadow-emerald-600/20">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16"/>
            </svg>
            Nueva caja
        </button>
        <button onclick="document.getElementById('modal-nueva').classList.remove('hidden')"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-5 py-3 rounded-xl transition shadow-sm shadow-blue-600/20">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva reparación
        </button>
    </div>
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
            <p class="text-2xl font-bold text-gray-900" data-contador="en_proceso">{{ $contadores['en_proceso'] }}</p>
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
            <p class="text-2xl font-bold text-gray-900" data-contador="arreglado">{{ $contadores['arreglado'] }}</p>
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
            <p class="text-2xl font-bold text-gray-900" data-contador="entregado">{{ $contadores['entregado'] }}</p>
            <p class="text-xs text-gray-500">Entregados</p>
        </div>
    </div>
</div>

{{-- ============================== --}}
{{-- FILTROS — form GET unificado   --}}
{{-- ============================== --}}
@php
    // Los filtros vienen revisados del controlador (no de request()): el select, las fechas y
    // el texto de la lista muestran exactamente lo que se filtró. Ver ReparacionController::index
    // y App\Support\FiltroPeriodo::delPedido.
    $fEstado   = $filtros['estado'];     // todos, en_proceso, arreglado o entregado
    $fPeriodo  = $filtros['periodo'];    // uno de FiltroPeriodo::PERIODOS
    $fDesde    = $filtros['desde'];      // YYYY-MM-DD o null (ya en orden)
    $fHasta    = $filtros['hasta'];
    $fBuscar   = $filtros['buscar'];     // lo escrito, aunque sea una letra (el campo y la × lo muestran)
    $fBuscando = $filtros['buscando'];   // si de verdad se buscó: desde 2 letras

    // Solo en_proceso no necesita filtro de fechas
    $sinFiltroFecha  = $fEstado === 'en_proceso';

    // «Limpiar filtros» vuelve a la página de inicio: «En proceso», sin búsqueda. Sale solo
    // cuando lo que se ve es otra cosa: otro estado (con cualquier periodo) o algo escrito en el
    // buscador, aunque sea una letra (el campo y la × la muestran). En «En proceso» el periodo y
    // las fechas no cuentan: ahí no se filtra por fecha, y al volver de «Personalizado» las
    // fechas siguen en la URL (los campos ocultos se envían igual). Si saliera en el inicio,
    // llevaría a la misma página
    $hayFiltrosExtra = $fBuscar || $fEstado !== 'en_proceso';
@endphp

{{-- El buscador actualiza la lista mientras se escribe, desde 3 letras
     (resources/js/busqueda-en-vivo.js): pide esta misma página con la búsqueda nueva y
     reemplaza cada parte data-en-vivo="nombre" por la de la respuesta. Por eso esas
     partes existen siempre, aunque estén vacías. Estado y periodo siguen recargando. --}}
<form method="GET" action="{{ route('admin.reparaciones.index') }}" id="form-filtros" data-busqueda-en-vivo>
    <div class="flex flex-wrap gap-3 mb-3">

        {{-- Select para filtrar por estado de la reparación --}}
        <select name="estado" id="select-estado"
                class="px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer">

            {{-- Mostrar todas las reparaciones --}}
            <option value="todos"      {{ $fEstado === 'todos' ? 'selected' : '' }}>Todos</option>

            {{-- Reparaciones que aún están en proceso --}}
            <option value="en_proceso" {{ (!$fEstado || $fEstado === 'en_proceso') ? 'selected' : '' }}>En proceso</option>

            {{-- Reparaciones arregladas --}}
            <option value="arreglado"  {{ $fEstado === 'arreglado'              ? 'selected' : '' }}>Arreglados</option>

            {{-- Reparaciones ya entregadas --}}
            <option value="entregado"  {{ $fEstado === 'entregado'              ? 'selected' : '' }}>Entregados</option>
        </select>

        {{-- Select de periodo.
             Se oculta cuando el estado no necesita filtros de fecha --}}
        <div id="periodo-container" class="{{ $sinFiltroFecha ? 'hidden' : '' }}">

            <select name="periodo" id="select-periodo"
                    class="h-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer">

                {{-- Mostrar reparaciones del día actual --}}
                <option value="hoy"          {{ $fPeriodo === 'hoy'          ? 'selected' : '' }}>Hoy</option>

                {{-- Mostrar reparaciones de la semana --}}
                <option value="semana"        {{ $fPeriodo === 'semana'        ? 'selected' : '' }}>Esta semana</option>

                {{-- Mostrar reparaciones del mes actual --}}
                <option value="mes"           {{ $fPeriodo === 'mes' ? 'selected' : '' }}>Este mes</option>

                {{-- Mostrar reparaciones del mes pasado --}}
                <option value="mes_pasado"    {{ $fPeriodo === 'mes_pasado'    ? 'selected' : '' }}>Mes pasado</option>

                {{-- Últimos 3 y 6 meses: desde el mismo día de hace 3 o 6 meses hasta hoy
                     incluido (App\Support\FiltroPeriodo) --}}
                <option value="trimestre"     {{ $fPeriodo === 'trimestre'     ? 'selected' : '' }}>Últimos 3 meses</option>
                <option value="semestre"      {{ $fPeriodo === 'semestre'      ? 'selected' : '' }}>Últimos 6 meses</option>

                {{-- Mostrar reparaciones del año actual --}}
                <option value="anio"          {{ $fPeriodo === 'anio'          ? 'selected' : '' }}>Este año</option>

                {{-- Activar búsqueda personalizada por fechas --}}
                <option value="personalizado" {{ $fPeriodo === 'personalizado' ? 'selected' : '' }}>Personalizado</option>
            </select>
        </div>

        {{-- Campos de fecha personalizada.
             Solo se muestran cuando el periodo es "personalizado" --}}
        <div id="fechas-personalizado"
             class="{{ $fPeriodo === 'personalizado' && !$sinFiltroFecha ? 'flex gap-2' : 'hidden' }}">

            {{-- Fecha inicial --}}
            <input type="date" name="desde" value="{{ $fDesde }}"
                   class="px-3 py-3 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">

            {{-- Fecha final --}}
            <input type="date" name="hasta" value="{{ $fHasta }}"
                   class="px-3 py-3 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
        </div>

        {{-- Contenedor del buscador --}}
        <div class="flex flex-1 min-w-56 gap-2">

            {{-- Input de búsqueda --}}
            <div class="relative flex-1">

                {{-- Icono de búsqueda --}}
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                {{-- Campo para buscar por cliente, teléfono o marca --}}
                <input type="text" name="buscar" id="input-buscar" value="{{ $fBuscar }}"
                       placeholder="Buscar por cliente, teléfono, marca..."
                       autocomplete="off" enterkeyhint="search"
                       class="w-full pl-12 pr-4 py-3 bg-white border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
            </div>

            {{-- Botón para ejecutar la búsqueda --}}
            <button type="submit"
                    class="px-4 py-3 bg-white border border-gray-200 rounded-xl text-gray-500 hover:bg-gray-50 hover:text-blue-600 transition"
                    title="Buscar">

                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>

            {{-- Botón para limpiar únicamente el buscador --}}
            <div data-en-vivo="limpiar-busqueda" class="contents">
                @if($fBuscar)
                    <button type="button" onclick="limpiarBusqueda()"
                            class="px-4 py-3 bg-white border border-gray-200 rounded-xl text-gray-500 hover:bg-red-50 hover:text-red-500 transition"
                            title="Limpiar búsqueda" aria-label="Limpiar búsqueda">

                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        {{-- Botón para reiniciar todos los filtros --}}
        <div data-en-vivo="limpiar-filtros" class="contents">
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

    </div>
</form>

{{-- Indicador de filtros activos. aria-live va en este contenedor, que no se reemplaza:
     la búsqueda en vivo cambia lo de adentro (data-en-vivo) y así el lector de pantalla
     lo anuncia. Si se reemplazara la zona entera, no anunciaría nada. --}}
<div class="flex items-center justify-between mb-6" aria-live="polite">
    <p data-en-vivo="indicador" class="text-sm text-gray-500">
        @php
            $estadoLabel = match($fEstado) {
                'en_proceso' => 'en proceso',
                'arreglado'  => 'arregladas',
                'entregado'  => 'entregadas',
                default      => null,
            };

            // El periodo como lo dice la lista: «de este mes», «de los últimos 6 meses (del
            // 23/05/2026 a hoy)», «del 10/03/2026 al 12/03/2026»... (FiltroPeriodo::descripcion,
            // con las mismas fechas que filtró el controlador). En proceso no filtra por fecha
            $periodoLabel = $sinFiltroFecha ? '' : \App\Support\FiltroPeriodo::descripcion($fPeriodo, $fDesde, $fHasta);

            // Cuáles se ven: «todas las reparaciones de este mes», «reparaciones entregadas de…»
            $cuales = trim(($estadoLabel ? "reparaciones {$estadoLabel}" : 'todas las reparaciones') . ' ' . $periodoLabel);
        @endphp

        {{-- Con búsqueda (desde 2 letras, como el servidor) dice entre cuáles buscó: la búsqueda
             respeta el estado y el periodo, y sin decirlo parecía que no existía.
             total(): todas las que encontró, aunque la página muestre 25 --}}
        @if($fBuscando)
            <strong>{{ $reparaciones->total() }}</strong> resultado{{ $reparaciones->total() !== 1 ? 's' : '' }}
            para "<strong>{{ $fBuscar }}</strong>" entre {{ $estadoLabel ? 'las ' : '' }}{{ $cuales }}
        @else
            Mostrando {{ $cuales }}
        @endif
    </p>
</div>

{{-- Tabla --}}
<div data-en-vivo="resultados" class="bg-white rounded-2xl border border-gray-100 overflow-hidden transition-opacity">

    @if($reparaciones->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">ID</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Fecha</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Cliente</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Equipo</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Descripción</th>
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
                    @include('admin.reparaciones.partials.fila')
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
        {{-- Con búsqueda (la en vivo sale a cada rato) no es que no haya reparaciones. La
             búsqueda respeta el periodo: si el periodo filtra, puede estar en otro --}}
        @if($fBuscando)
            <p class="text-gray-400 text-sm">No se encontraron reparaciones con esa búsqueda</p>
            <p class="text-gray-300 text-xs mt-1">Prueba con el nombre, el teléfono, la marca o el modelo{{ $sinFiltroFecha ? '' : ', o con otro periodo' }}</p>
        @else
            <p class="text-gray-400 text-sm">No hay reparaciones registradas</p>
            <p class="text-gray-300 text-xs mt-1">Crea la primera con el botón de arriba</p>
        @endif
    </div>
    @endif
</div>
{{-- Paginación, también con búsqueda (solo cuando hay más de una página). Los enlaces
     llevan la búsqueda y los filtros (withQueryString en el controlador) --}}
<div data-en-vivo="paginacion">
    @if($reparaciones->hasPages())
    <div class="mt-6 flex justify-center">
        {{ $reparaciones->links() }}
    </div>
    @endif
</div>

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
                        {{-- Si no tiene WhatsApp, no se le envía el aviso de "reparación lista" --}}
                        <input type="hidden" name="tiene_whatsapp" value="0">
                        <label class="mt-1.5 flex items-center gap-1.5 text-xs text-gray-500 cursor-pointer select-none">
                            <input type="checkbox" name="tiene_whatsapp" value="1" {{ old('tiene_whatsapp', '1') ? 'checked' : '' }}
                                   class="w-3.5 h-3.5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                            Tiene WhatsApp
                        </label>
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
                {{-- La lista se pone al día sola cuando otra persona ocupa o libera una caja --}}
                <select name="caja_id" required onchange="document.getElementById('aviso-caja-ocupada').classList.add('hidden')"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    <option value="">Seleccionar caja...</option>
                    @foreach($cajasLibres as $caja)
                        <option value="{{ $caja->id }}" {{ old('caja_id') == $caja->id ? 'selected' : '' }}>
                            {{ $caja->nombre_display }}
                        </option>
                    @endforeach
                </select>
                <p id="aviso-caja-ocupada" role="status" class="hidden mt-1 text-xs font-medium text-amber-700"></p>
            </div>

            {{-- Falla --}}
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Descripción de la falla</label>
                <textarea name="descripcion_falla" rows="2" placeholder="¿Qué le pasa al equipo?"
                          class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition resize-none">{{ old('descripcion_falla') }}</textarea>
            </div>

            {{-- Display usado (se descuenta del inventario) --}}
            @include('admin.reparaciones.partials.campo-display', ['prefijo' => 'nueva', 'conOld' => true])

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

            {{-- Errores del formulario de reparación (bag 'default') --}}
            @if ($errors->getBag('default')->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->getBag('default')->all() as $error)
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
{{-- MODAL - Nueva Caja              --}}
{{-- ============================== --}}
<div id="modal-nueva-caja" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('modal-nueva-caja').classList.add('hidden')"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm">

        <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 rounded-t-2xl flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Nueva caja</h3>
                <p class="text-sm text-gray-500">Registra un espacio físico nuevo</p>
            </div>
            <button onclick="document.getElementById('modal-nueva-caja').classList.add('hidden')"
                    class="p-2 rounded-xl hover:bg-gray-100 transition">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.cajas.store') }}" class="p-6 space-y-5">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Grupo (letra) *</label>
                    <input type="text" name="grupo" required maxlength="1" placeholder="A"
                           value="{{ old('grupo') }}"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm uppercase focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Número *</label>
                    <input type="number" name="numero" required min="1" placeholder="1"
                           value="{{ old('numero') }}"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                </div>
            </div>

            {{-- Errores del formulario de caja (bag 'caja') --}}
            @if ($errors->getBag('caja')->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->getBag('caja')->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('modal-nueva-caja').classList.add('hidden')"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition shadow-sm shadow-emerald-600/20">
                    Crear caja
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
            {{-- Tiempo real: otra persona cambió esta reparación mientras el modal está abierto.
                 No se tocan los campos que se están escribiendo; se ofrece cargar sus cambios. --}}
            <div id="edit-aviso-cambio" role="status"
                 class="hidden bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-xl px-4 py-3">
                <p id="edit-aviso-cambio-texto"></p>
                <button type="button" onclick="cargarCambiosEdicion()"
                        class="mt-2 px-3 py-1.5 text-xs font-semibold text-amber-800 bg-white border border-amber-300 rounded-lg hover:bg-amber-100 transition">
                    Cargar sus cambios
                </button>
            </div>

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
                        <label class="mt-1.5 flex items-center gap-1.5 text-xs text-gray-500 cursor-pointer select-none">
                            <input type="checkbox" id="edit-tiene_whatsapp"
                                   class="w-3.5 h-3.5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                            Tiene WhatsApp
                        </label>
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

            {{-- Display usado: al guardar se devuelve o descuenta stock según el cambio --}}
            @include('admin.reparaciones.partials.campo-display', ['prefijo' => 'edit', 'conOld' => false])

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
    <div class="absolute inset-0 bg-black/50" onclick="cerrarModalAbono()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Registrar abono</h3>
                <p class="text-sm text-gray-500" id="abono-cliente-nombre"></p>
            </div>
            <button onclick="cerrarModalAbono()"
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
                {{-- Tiempo real: otra persona cobró o cambió esta reparación con el modal abierto --}}
                <p id="abono-aviso-cambio" role="status"
                   class="hidden mt-2 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2"></p>
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
                <button type="button" onclick="cerrarModalAbono()"
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

{{-- ============================== --}}
{{-- MODAL - Pasar a Arreglado        --}}
{{-- ============================== --}}
{{-- Sale al pasar de «En proceso» a «Arreglado» cuando al cliente se le avisaría por WhatsApp
     (data-avisar-a de la fila). Trae marcado el aviso; si se quita (por ejemplo, el cliente
     está en el taller), el estado cambia igual y no se le escribe. Ver abrirModalArreglado().
     No usa SweetAlert: va en el camino del cambio de estado, y si el CDN no carga no puede
     romper el select. --}}
<div id="modal-arreglado" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="arreglado-titulo">
    <div class="absolute inset-0 bg-black/50" onclick="cancelarArreglado()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900" id="arreglado-titulo">Pasar a Arreglado</h3>
                <p class="text-sm text-gray-500" id="arreglado-cliente"></p>
            </div>
            <button type="button" onclick="cancelarArreglado()" aria-label="Cerrar"
                    class="p-2 rounded-xl hover:bg-gray-100 transition">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            {{-- Toda la caja se puede tocar (es el label): en el iPad la casilla sola es chica --}}
            <label for="arreglado-avisar"
                   class="flex items-start gap-3 p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition">
                <input type="checkbox" id="arreglado-avisar" class="mt-0.5 w-5 h-5 accent-green-600 cursor-pointer">
                <span>
                    <span class="block text-sm font-medium text-gray-900">Enviar mensaje por WhatsApp</span>
                    <span class="block text-sm text-gray-500">Al <span id="arreglado-telefono"></span>, para avisarle que ya está listo</span>
                </span>
            </label>
            <p class="text-xs text-gray-400">Quítala si el cliente está en el taller: el estado cambia igual y no se le escribe.</p>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" id="arreglado-cancelar" onclick="cancelarArreglado()"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                    Cancelar
                </button>
                <button type="button" id="arreglado-confirmar" onclick="confirmarArreglado()"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition shadow-sm shadow-green-600/20">
                    Pasar a Arreglado
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;


    // ══════════════════════════════════════
// MODAL EDITAR
// ══════════════════════════════════════
// El modal no guarda la fila: la búsqueda en vivo y el tiempo real reemplazan las filas, y
// una guardada quedaría vieja (fuera de la página). Cuando hace falta, se busca la que está
// en pantalla con filaDe(editReparacionId).
let editReparacionId = null;

// Cómo estaba la reparación al abrir el modal. Se manda al guardar: si otra persona
// cambió alguno de estos campos mientras tanto, el servidor no guarda (409).
let datosAlAbrir = null;
const CAMPOS_EDITABLES = @js(App\Models\Reparacion::CAMPOS_EDITABLES);

// Encabezado X-Socket-ID para que el servidor no le reenvíe a esta pantalla sus
// propios cambios en tiempo real (lo da resources/js/tiempo-real.js si hay Pusher)
function cabecerasEnVivo() {
    return window.cabecerasTiempoReal ? window.cabecerasTiempoReal() : {};
}

function abrirModalEditar(btn) {
    mostrarModalEditar(JSON.parse(btn.closest('tr').dataset.edit));
}

/**
 * Llena el modal con los datos de una reparación (el data-edit de su fila) y lo abre.
 * Lo usan Editar (la fila de la pantalla) y «Cargar sus cambios» (la que manda el servidor).
 */
function mostrarModalEditar(datos) {
    editReparacionId = datos.id;
    datosAlAbrir = datos;
    ocultarAvisoCambioEdicion();

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

    document.getElementById('edit-tiene_whatsapp').checked = datos.tiene_whatsapp !== false;

    // ── Display usado ──
    prepararDisplayEdicion(datos);

    // ── Abonos ──
    const lista  = document.getElementById('edit-abonos-lista');
    const vacio  = document.getElementById('edit-abonos-vacio');
    lista.innerHTML = '';

    if (datos.abonos && datos.abonos.length > 0) {
        vacio.classList.add('hidden');
        // Se arma con textContent: la nota la escribe el usuario (ej: "pagó <50%")
        datos.abonos.forEach(abono => {
            const item = document.createElement('div');
            item.className = 'flex items-start justify-between gap-3 bg-gray-50 rounded-xl px-4 py-3';

            const info  = document.createElement('div');
            const fecha = document.createElement('p');
            fecha.className   = 'text-xs text-gray-400';
            fecha.textContent = abono.fecha;
            info.appendChild(fecha);

            if (abono.nota) {
                const nota = document.createElement('p');
                nota.className   = 'text-xs text-gray-500 mt-0.5 italic';
                nota.textContent = abono.nota;
                info.appendChild(nota);
            }

            const monto = document.createElement('span');
            monto.className   = 'text-sm font-semibold text-green-600 whitespace-nowrap';
            monto.textContent = '$' + abono.monto.toLocaleString('es-CO');

            item.append(info, monto);
            lista.appendChild(item);
        });
    } else {
        vacio.classList.remove('hidden');
    }

    document.getElementById('edit-errores').classList.add('hidden');
    document.getElementById('edit-errores-lista').innerHTML = '';
    document.getElementById('modal-editar').classList.remove('hidden');
}

// Paga de un clic el saldo pendiente completo desde el botón de la fila.
// Lee el saldo vigente directo de la celda (única fuente de verdad, siempre fresca)
// y reutiliza el mismo endpoint de abonos (con lockForUpdate del lado del servidor).
function pagarSaldoTotal(reparacionId) {
    const fila = document.querySelector(`tr[data-id="${reparacionId}"]`);
    if (!fila) return;

    const saldo = leerSaldoDeFila(fila);
    if (saldo <= 0) {
        mostrarNotificacion('Esta reparación ya está pagada completamente.', 'error');
        return;
    }

    const montoFmt = '$' + saldo.toLocaleString('es-CO');

    Swal.fire({
        title: '¿Pagar saldo total?',
        html: `Se registrará un pago de <strong>${montoFmt}</strong> y la reparación quedará pagada al 100%.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, pagar total',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    }).then((result) => {
        if (!result.isConfirmed) return;

        // Mientras el diálogo estaba abierto pudo cobrar otra persona (la fila se pone al
        // día sola): si el saldo ya no es el que se confirmó, no se cobra
        const filaActual  = filaDe(reparacionId);
        const saldoActual = filaActual ? leerSaldoDeFila(filaActual) : null;

        if (saldoActual !== saldo) {
            mostrarNotificacion(
                saldoActual === null ? 'Esta reparación ya no está en la lista. Recarga la página para revisarla.'
                    : saldoActual <= 0 ? 'Otra persona ya registró el pago: esta reparación quedó pagada.'
                    : `El saldo cambió: ahora es $${saldoActual.toLocaleString('es-CO')}. Revisa y vuelve a intentar.`,
                'warning', 6000
            );
            return;
        }

        const btn = filaActual.querySelector('button[data-btn="pagar-total"]');
        if (btn) btn.disabled = true;

        fetch(`/admin/reparaciones/${reparacionId}/abono`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                ...cabecerasEnVivo(),
            },
            body: JSON.stringify({ monto: saldo, nota: 'Pago total del saldo' })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                aplicarPagoEnFila(reparacionId, saldo, 'Pago total del saldo', data);
                mostrarNotificacion('Saldo pagado en su totalidad');
            } else {
                mostrarNotificacion('Error: ' + data.message, 'error');
                if (btn) btn.disabled = false;
            }
            refrescarFila(reparacionId);
        })
        .catch(() => {
            mostrarNotificacion('Error de conexión', 'error');
            if (btn) btn.disabled = false;
        });
    });
}

function cerrarModalEditar() {
    const fila = editReparacionId ? filaDe(editReparacionId) : null;

    document.getElementById('modal-editar').classList.add('hidden');
    ocultarAvisoCambioEdicion();
    editReparacionId = null;
    datosAlAbrir = null;

    // Si otra persona la cambió de estado mientras estaba abierta, ahora sí sale del filtro
    if (fila) revisarSalidaDeFiltro(fila);
}

// Aviso arriba del modal de edición: otra persona cambió esta reparación
function mostrarAvisoCambioEdicion(texto) {
    document.getElementById('edit-aviso-cambio-texto').textContent = texto;
    document.getElementById('edit-aviso-cambio').classList.remove('hidden');
}

function ocultarAvisoCambioEdicion() {
    document.getElementById('edit-aviso-cambio').classList.add('hidden');
}

// "Cargar sus cambios": trae la reparación del servidor y vuelve a llenar el modal con ella.
// Usa la fila que manda /sincronizar y no la de la pantalla: la búsqueda en vivo pudo
// sacarla de la lista (si sigue en pantalla, sincronizarFilas también la pone al día).
// Lo que se había escrito y no se guardó se pierde (es lo que pidió quien tocó el botón).
// Si no se pudo traer (sin conexión o sin sesión), el modal y el aviso se quedan como estaban.
async function cargarCambiosEdicion() {
    if (!editReparacionId) return;

    const id   = String(editReparacionId);
    const data = await sincronizarFilas([id]);

    // Mientras llegaba, se cerró el modal (o se abrió el de otra reparación): no se toca
    if (String(editReparacionId) !== id) return;

    if (!data) {
        mostrarNotificacion('No se pudieron cargar los cambios. Revisa la conexión e intenta otra vez.', 'error', 6000);
        return;
    }

    const fila = data.filas?.[id] ? crearFila(data.filas[id]) : null;
    if (!fila) {
        mostrarNotificacion('Esta reparación ya no existe. Recarga la página para revisar la lista.', 'error', 6000);
        return;
    }

    mostrarModalEditar(JSON.parse(fila.dataset.edit));
}

function guardarEdicion() {
    const btn    = document.getElementById('edit-btn-guardar');
    const nombre = document.getElementById('edit-nombre_cliente').value.trim();
    const marca  = document.getElementById('edit-marca').value.trim();
    const valor  = document.getElementById('edit-valor_total').value;

    if (!nombre || !marca || !valor) {
        mostrarErroresEdicion(['Nombre, marca y valor total son obligatorios.']);
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const payload = {
        _method:           'PUT',
        nombre_cliente:    nombre,
        telefono_cliente:  document.getElementById('edit-telefono_cliente').value.trim(),
        tiene_whatsapp:    document.getElementById('edit-tiene_whatsapp').checked,
        marca:             marca,
        modelo:            document.getElementById('edit-modelo').value.trim(),
        descripcion_falla: document.getElementById('edit-descripcion_falla').value.trim(),
        valor_total:       valor,
        costo_repuestos:   document.getElementById('edit-costo_repuestos').value || null,
        usa_display:       document.getElementById('edit-usa_display').checked,
        display_id:        document.getElementById('edit-display_id').value || null,
        display_cantidad:  parseInt(document.getElementById('edit-display_cantidad').value) || 1,
        // Cómo estaba al abrir el modal: el servidor rechaza si otra persona lo cambió
        original:          datosAlAbrir
            ? Object.fromEntries(CAMPOS_EDITABLES.map(campo => [campo, datosAlAbrir[campo]]))
            : null,
    };

    fetch(`/admin/reparaciones/${editReparacionId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'X-HTTP-Method-Override': 'PUT',
            ...cabecerasEnVivo(),
        },
        body: JSON.stringify(payload),
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.textContent = 'Guardar cambios';

        if (data.success) {
            actualizarFilaEdicion(data.data);
            actualizarStocksDisplays(data.stocks);
            cerrarModalEditar();
            mostrarNotificacion(data.message);
            refrescarFila(data.data.id);
        } else if (data.conflicto) {
            // Otra persona la cambió: no se guardó nada; se ofrece cargar sus cambios
            mostrarAvisoCambioEdicion(data.message);
            document.getElementById('edit-errores').classList.add('hidden');
        } else {
            const errores = data.errors
                ? Object.values(data.errors).flat()
                : [data.message];
            mostrarErroresEdicion(errores);
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = 'Guardar cambios';
        mostrarNotificacion('Error de conexión', 'error');
    });
}

// Pinta la lista de errores del modal de edición. textContent: los mensajes
// pueden incluir datos escritos por el usuario (ej: el nombre de un producto).
function mostrarErroresEdicion(errores) {
    document.getElementById('edit-errores-lista').replaceChildren(...errores.map(texto => {
        const item = document.createElement('li');
        item.textContent = texto;
        return item;
    }));
    document.getElementById('edit-errores').classList.remove('hidden');
}

// Pinta en la fila lo que se acaba de guardar. La fila que está en pantalla ahora: la
// búsqueda en vivo pudo reemplazarla o sacarla de la lista (entonces no hay nada que pintar).
// Después guardarEdicion() llama a refrescarFila(): el servidor tiene la última palabra.
function actualizarFilaEdicion(datos) {
    const fila = filaDe(datos.id);
    if (!fila) return;

    // textContent (no innerHTML): son datos escritos por el usuario
    const celdaCliente = fila.querySelector('[data-celda="cliente"]');
    celdaCliente.querySelector('[data-campo="nombre"]').textContent   = datos.nombre_cliente;
    celdaCliente.querySelector('[data-campo="telefono"]').textContent = datos.telefono_cliente || 'Sin teléfono';

    const celdaEquipo = fila.querySelector('[data-celda="equipo"]');
    celdaEquipo.querySelector('[data-campo="marca"]').textContent  = datos.marca;
    celdaEquipo.querySelector('[data-campo="modelo"]').textContent = datos.modelo || '—';

    const etiquetaDisplay = celdaEquipo.querySelector('[data-campo="display"]');
    const textoDisplay    = datos.display_id ? `Display: ${datos.display_nombre} ×${datos.display_cantidad}` : '';
    etiquetaDisplay.querySelector('span').textContent = textoDisplay;
    etiquetaDisplay.querySelector('span').title       = textoDisplay;
    etiquetaDisplay.classList.toggle('hidden', !datos.display_id);

    // El recorte viene del servidor (Reparacion::descripcionCorta), igual que en Blade
    const descripcion = datos.descripcion_falla || '';
    const celdaDescripcion = fila.querySelector('[data-celda="descripcion"]');
    celdaDescripcion.textContent = datos.descripcion_corta || '—';
    celdaDescripcion.title = descripcion;
    celdaDescripcion.classList.toggle('text-purple-600', !!descripcion);
    celdaDescripcion.classList.toggle('font-medium', !!descripcion);
    celdaDescripcion.classList.toggle('text-gray-400', !descripcion);

    fila.querySelector('[data-celda="valor"]').textContent = '$' + datos.valor_total.toLocaleString('es-CO');

    const abonado = leerMonto(fila, 'abonado');
    const saldo   = datos.valor_total - abonado;
    fila.querySelector('[data-celda="saldo"]').innerHTML = `
        <span class="${saldo > 0 ? 'text-red-600' : 'text-green-600'} font-medium">
            $${saldo.toLocaleString('es-CO')}
        </span>`;

    // Si el valor total cambió, el saldo también: mostrar u ocultar Abono y Pagar total
    actualizarBotonesDePago(fila, saldo);

    // Actualizar data-edit con valores frescos para una segunda edición
    fila.dataset.edit = JSON.stringify({
        ...JSON.parse(fila.dataset.edit),
        ...datos,
    });
}


// ══════════════════════════════════════
    // CAMBIAR ESTADO (AJAX sin recargar)
    // ══════════════════════════════════════
    /**
     * El select de estado de la fila. De «En proceso» a «Arreglado», si al cliente se le
     * avisaría por WhatsApp (data-avisar-a, lo pone el servidor), primero pregunta si se le
     * avisa (modal-arreglado). Si no, guarda directo.
     */
    function cambiarEstado(select, reparacionId) {
        const fila = select.closest('tr');

        if (fila.dataset.estado === 'en_proceso' && select.value === 'arreglado' && fila.dataset.avisarA) {
            abrirModalArreglado(select, reparacionId);
            return;
        }

        guardarEstado(select, reparacionId, select.value);
    }

    /**
     * Guarda el estado. avisar: lo que se eligió en el modal al pasar a «Arreglado» (true o
     * false). Sin modal no se manda, y el servidor avisa si corresponde, como siempre.
     */
    function guardarEstado(select, reparacionId, nuevoEstado, avisar) {
        // Con el modal abierto, el tiempo real pudo reemplazar la fila: se usa la de ahora
        const fila = filaDe(reparacionId) || select.closest('tr');
        select = fila.querySelector('select') || select;
        select.value = nuevoEstado;

        const estadoAnterior = fila.dataset.estado;
        const cuerpo = { estado: nuevoEstado };
        if (avisar !== undefined) cuerpo.avisar_whatsapp = avisar;

        // Feedback visual inmediato: borde verde en el select
        select.classList.add('ring-2', 'ring-green-400');

        fetch(`/admin/reparaciones/${reparacionId}/estado`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                ...cabecerasEnVivo(),
            },
            body: JSON.stringify(cuerpo)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Si mientras tanto el tiempo real reemplazó la fila, la respuesta va a la
                // que está en pantalla ahora
                const filaActual = filaDe(reparacionId) || fila;

                // Actualizar el data-estado de la fila (para que los filtros funcionen)
                filaActual.dataset.estado = nuevoEstado;

                // Actualizar contadores sin recargar
                actualizarContadores(estadoAnterior, nuevoEstado);

                // Mostrar notificación flotante. Si se eligió no avisar, se dice aquí: el
                // servidor no manda aviso de WhatsApp (lo decidió quien lo marcó)
                mostrarNotificacion(avisar === false
                    ? 'Estado actualizado. No se avisó por WhatsApp.'
                    : 'Estado actualizado correctamente');

                // Aviso por WhatsApp: el intento nuevo solo viene al pasar de "En proceso"
                // a "Arreglado"; el estado actual viene siempre, y con el nuevo estado de
                // la fila se muestra u oculta el botón "Enviar WhatsApp".
                aplicarRespuestaWhatsapp(filaActual, data);

                // ── Animación de desaparición si hay filtro de estado activo ──
                //
                // Leemos el parámetro "estado" de la URL actual.
                // Si el usuario está filtrado (ej: ?estado=en_proceso) y acaba de
                // cambiar la fila a otro estado, esa fila ya no pertenece al filtro
                // activo → la animamos para que desaparezca sin recargar la página.
                const filtroActivo = document.getElementById('select-estado')?.value
                    || new URLSearchParams(window.location.search).get('estado');

                // Solo actuamos si hay un filtro específico Y el nuevo estado es diferente.
                // Si no hay filtro (null) o es "todos", dejamos la fila como está.
                const debeDesaparecer = filtroActivo
                    && filtroActivo !== 'todos'
                    && filtroActivo !== nuevoEstado;

                if (debeDesaparecer) {
                    desvanecerFila(filaActual);
                } else {
                    // Sin filtro activo o el estado coincide con el filtro:
                    // solo quitamos el anillo de feedback visual después de 1 segundo
                    setTimeout(() => {
                        select.classList.remove('ring-2', 'ring-green-400');
                    }, 1000);
                }

                // El servidor tiene la última palabra: fila y contadores exactos
                refrescarFila(reparacionId);

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

    // ══════════════════════════════════════
    // PASAR A ARREGLADO: ¿SE LE AVISA POR WHATSAPP?
    // ══════════════════════════════════════
    // La reparación que espera la respuesta del modal: { id, select, estadoAnterior }. El modal
    // no se queda con la fila: el tiempo real puede reemplazarla mientras está abierto, y al
    // cerrarlo se busca la de ahora con filaDe(id).
    let arregladoPendiente = null;

    function abrirModalArreglado(select, reparacionId) {
        const fila   = select.closest('tr');
        const nombre = fila.querySelector('[data-campo="nombre"]').textContent.trim();
        const equipo = ['marca', 'modelo']
            .map(campo => fila.querySelector(`[data-campo="${campo}"]`)?.textContent.trim())
            .filter(texto => texto && texto !== '—')
            .join(' ');

        arregladoPendiente = { id: reparacionId, select, estadoAnterior: fila.dataset.estado };

        // textContent (no innerHTML): son datos escritos por el usuario. El celular viene del
        // servidor ya revisado (solo números y espacios)
        document.getElementById('arreglado-cliente').textContent  = equipo ? `${nombre} · ${equipo}` : nombre;
        document.getElementById('arreglado-telefono').textContent = fila.dataset.avisarA;
        document.getElementById('arreglado-avisar').checked       = true;

        document.getElementById('modal-arreglado').classList.remove('hidden');
        document.addEventListener('keydown', cancelarArregladoConEscape);
        document.getElementById('arreglado-confirmar').focus();
    }

    /** Cierra el modal y devuelve lo que esperaba respuesta (null si ya estaba cerrado). */
    function cerrarModalArreglado() {
        const pendiente = arregladoPendiente;

        arregladoPendiente = null;
        document.getElementById('modal-arreglado').classList.add('hidden');
        document.removeEventListener('keydown', cancelarArregladoConEscape);

        return pendiente;
    }

    /**
     * Cancelar, la ×, tocar afuera o Escape: no se guarda nada y el select vuelve al estado de
     * la fila (si otra persona la cambió mientras tanto, al de ahora).
     */
    function cancelarArreglado() {
        const pendiente = cerrarModalArreglado();
        if (!pendiente) return;

        const fila   = filaDe(pendiente.id);
        const select = fila?.querySelector('select') || pendiente.select;
        select.value = fila?.dataset.estado || pendiente.estadoAnterior;
    }

    /** «Pasar a Arreglado»: guarda, y avisa al cliente solo si la opción quedó marcada. */
    function confirmarArreglado() {
        const avisar    = document.getElementById('arreglado-avisar').checked;
        const pendiente = cerrarModalArreglado();
        if (!pendiente) return;

        guardarEstado(pendiente.select, pendiente.id, 'arreglado', avisar);
    }

    function cancelarArregladoConEscape(evento) {
        if (evento.key === 'Escape') cancelarArreglado();
    }

    /**
     * Actualiza los números de las tarjetas de resumen.
     * Resta 1 al estado anterior y suma 1 al nuevo.
     */
    function actualizarContadores(estadoAnterior, nuevoEstado) {
        const anterior = document.querySelector(`[data-contador="${estadoAnterior}"]`);
        const nuevo    = document.querySelector(`[data-contador="${nuevoEstado}"]`);

        if (anterior) anterior.textContent = Math.max(0, (parseInt(anterior.textContent) || 0) - 1);
        if (nuevo)    nuevo.textContent    = (parseInt(nuevo.textContent) || 0) + 1;
    }

    /**
     * Pone los números exactos que mandó el servidor (tiempo real o puesta al día).
     * contadores = { en_proceso: 4, arreglado: 2, entregado: 31 }
     */
    function pintarContadores(contadores) {
        Object.entries(contadores || {}).forEach(([estado, total]) => {
            const tarjeta = document.querySelector(`[data-contador="${estado}"]`);
            if (tarjeta) tarjeta.textContent = total;
        });
    }

    /**
     * Saca una fila de la tabla con animación: se desvanece, colapsa su alto y se quita.
     * Se usa con style (no clases de Tailwind) para no depender de que la transición
     * esté configurada en el elemento.
     */
    function desvanecerFila(fila) {
        // Marca: el tiempo real no la reemplaza mientras se va (no reaparece de golpe)
        fila.dataset.saliendo = '1';

        // Fase 1 — fundido: opacity 0 en 300ms
        fila.style.transition = 'opacity 0.3s ease';
        fila.style.opacity    = '0';

        // Fase 2 — colapso de altura, para que las filas de abajo suban suavemente
        // en lugar de dejar un hueco vacío en la tabla
        setTimeout(() => {
            fila.style.height   = fila.offsetHeight + 'px';
            fila.style.overflow = 'hidden';

            // Reflow para que el navegador registre el alto fijo antes de cambiarlo
            fila.offsetHeight; // eslint-disable-line no-unused-expressions

            fila.style.transition    = 'height 0.3s ease, padding 0.3s ease';
            fila.style.height        = '0';
            fila.style.paddingTop    = '0';
            fila.style.paddingBottom = '0';
        }, 300);

        // Fase 3 — quitarla del DOM cuando terminaron las dos animaciones
        setTimeout(() => fila.remove(), 620);
    }

    /**
     * Muestra una notificación flotante que desaparece sola.
     * Tipo: 'success' (verde), 'warning' (ámbar) o 'error' (rojo)
     *
     * Todas van en un mismo contenedor: si salen dos seguidas
     * (ej: "Estado actualizado" + "WhatsApp enviado") se apilan en vez de taparse.
     *
     * duracion 0 = se queda hasta que la cierren con la ×. Es para lo que pide hacer
     * algo (ej: "No le llegó el WhatsApp... Llámalo").
     * opciones.alTocar: qué hacer al tocar el aviso (ej: ir a la fila); después se cierra.
     */
    function mostrarNotificacion(mensaje, tipo = 'success', duracion = 3000, opciones = {}) {
        const estilos = {
            success: 'bg-green-50 border border-green-200 text-green-700',
            warning: 'bg-amber-50 border border-amber-200 text-amber-700',
            error:   'bg-red-50 border border-red-200 text-red-700',
        };
        const icono = tipo === 'success'
            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>'
            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';

        let contenedor = document.getElementById('contenedor-notificaciones');
        if (!contenedor) {
            contenedor = document.createElement('div');
            contenedor.id = 'contenedor-notificaciones';
            contenedor.className = 'fixed top-6 right-6 z-50 flex flex-col items-end gap-2 pointer-events-none';
            document.body.appendChild(contenedor);
        }

        const noti = document.createElement('div');
        noti.className = `max-w-sm flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-sm font-medium transition-all duration-300 transform translate-x-full ${estilos[tipo] || estilos.success}`;
        noti.innerHTML = `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">${icono}</svg>`;

        // textContent: el mensaje puede traer datos del cliente o errores del servidor
        const texto = document.createElement('span');
        texto.textContent = mensaje;
        noti.appendChild(texto);

        const cerrar = () => {
            noti.classList.add('translate-x-full');
            setTimeout(() => noti.remove(), 300);
        };

        // Los avisos que se tocan o se cierran reciben clics (el contenedor no, para no
        // tapar los botones de la página que quedan detrás)
        if (opciones.alTocar || !duracion) {
            noti.style.pointerEvents = 'auto';
        }

        if (opciones.alTocar) {
            noti.style.cursor = 'pointer';
            noti.addEventListener('click', evento => {
                if (evento.target.closest('[data-cerrar]')) return;
                opciones.alTocar();
                cerrar();
            });
        }

        if (!duracion) {
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.dataset.cerrar = '';
            boton.textContent = '×';
            boton.setAttribute('aria-label', 'Cerrar aviso');
            boton.className = 'ml-1 px-1 text-lg leading-none opacity-60 hover:opacity-100';
            boton.addEventListener('click', cerrar);
            noti.appendChild(boton);
        }

        noti.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
        contenedor.appendChild(noti);

        // Animar entrada (deslizar desde la derecha)
        requestAnimationFrame(() => {
            noti.classList.remove('translate-x-full');
        });

        // Desaparecer después de la duración indicada (0 = hasta que lo cierren)
        if (duracion) {
            setTimeout(cerrar, duracion);
        }
    }

    // ══════════════════════════════════════
    // AVISO POR WHATSAPP
    // ══════════════════════════════════════
    // Colores del indicador. "leido" es un enviado que el cliente ya abrió (webhook de Meta).
    const ESTILOS_WHATSAPP = {
        enviado: 'bg-green-50 text-green-700',
        leido:   'bg-blue-50 text-blue-700',
        fallido: 'bg-red-50 text-red-600',
        omitido: 'bg-gray-100 text-gray-500',
    };

    /**
     * Pinta el indicador de WhatsApp de una fila a partir de data-whatsapp
     * (último intento: enviado, fallido u omitido) y de data-estado.
     * En "Arreglado" sin aviso exitoso agrega el botón para enviarlo o reintentarlo.
     */
    function pintarWhatsapp(fila) {
        const contenedor = fila.querySelector('[data-whatsapp]');
        if (!contenedor) return;

        const aviso = fila.dataset.whatsapp ? JSON.parse(fila.dataset.whatsapp) : null;
        contenedor.replaceChildren();

        if (aviso) {
            const estilo = ESTILOS_WHATSAPP[aviso.entrega === 'leido' ? 'leido' : aviso.estado] || ESTILOS_WHATSAPP.omitido;
            const chip = document.createElement('span');
            chip.className = `inline-block mt-1 text-[11px] font-medium px-2 py-0.5 rounded-lg whitespace-nowrap ${estilo}`;
            chip.textContent = aviso.etiqueta;
            chip.title = aviso.mensaje;
            contenedor.appendChild(chip);
        }

        if (fila.dataset.estado === 'arreglado' && (!aviso || aviso.estado !== 'enviado')) {
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'block mt-1 text-[11px] font-semibold text-green-600 hover:text-green-800 transition';
            // «Reintentar» solo después de un fallo. Si no se envió (omitido: por ejemplo, se
            // eligió no avisar al pasar a Arreglado), todavía no se intentó
            boton.textContent = aviso?.estado === 'fallido' ? 'Reintentar WhatsApp' : 'Enviar WhatsApp';
            boton.addEventListener('click', () => enviarWhatsapp(fila, boton));
            contenedor.appendChild(boton);
        }
    }

    /**
     * Aplica al indicador de la fila lo que respondió el servidor:
     *   data.whatsapp     → estado actual (último intento), para repintar la fila
     *   data.notificacion → resultado de un intento nuevo, para el aviso flotante
     *                       (dura más que los normales)
     */
    function aplicarRespuestaWhatsapp(fila, data) {
        if ('whatsapp' in data) {
            fila.dataset.whatsapp = data.whatsapp ? JSON.stringify(data.whatsapp) : '';
        }
        pintarWhatsapp(fila);

        if (data.notificacion) {
            const tipo = { enviado: 'success', omitido: 'warning', fallido: 'error' }[data.notificacion.estado] || 'warning';
            mostrarNotificacion(data.notificacion.mensaje, tipo, 6000);
        }
    }

    /**
     * Botón "Enviar / Reintentar WhatsApp" de las filas en "Arreglado".
     */
    function enviarWhatsapp(fila, boton) {
        const id = fila.dataset.id;
        boton.disabled = true;
        boton.textContent = 'Enviando...';

        fetch(`/admin/reparaciones/${id}/whatsapp`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                ...cabecerasEnVivo(),
            },
        })
        .then(res => res.json())
        .then(data => {
            // Enviar tarda unos segundos: si mientras tanto el tiempo real reemplazó la
            // fila, la respuesta va a la que está en pantalla ahora
            const filaActual = filaDe(id) || fila;

            if (data.success) {
                // Si ya se había enviado (por ejemplo desde otra pantalla), no hay intento
                // nuevo: la fila se pone al día y se explica por qué no salió otro mensaje.
                aplicarRespuestaWhatsapp(filaActual, data);
                if (!data.notificacion && data.message) mostrarNotificacion(data.message, 'warning', 6000);
            } else {
                mostrarNotificacion(data.message || 'No se pudo enviar el WhatsApp', 'error');
                pintarWhatsapp(filaActual);
            }

            refrescarFila(id);
        })
        .catch(() => {
            mostrarNotificacion('Error de conexión', 'error');
            pintarWhatsapp(filaDe(id) || fila);
        });
    }

    // ══════════════════════════════════════
    // DISPLAY USADO (modales crear / editar)
    // ══════════════════════════════════════
    /**
     * Opciones de cada select de displays ('nueva' y 'edit'), guardadas al cargar la página.
     * El buscador reconstruye el select desde aquí en vez de ocultar opciones con
     * "hidden", que Safari de iPhone/iPad ignora en la lista nativa.
     *   vacia:    la opción "Seleccionar display..."
     *   base:     las pantallas activas que mandó el servidor
     *   temporal: el display inactivo de la reparación que se está editando (o null)
     */
    const OPCIONES_DISPLAY = {};

    document.querySelectorAll('select.select-display').forEach(select => {
        OPCIONES_DISPLAY[select.id.replace('-display_id', '')] = {
            vacia:    select.options[0],
            base:     Array.from(select.options).filter(opcion => opcion.value),
            temporal: null,
        };
    });

    function toggleDisplay(prefijo) {
        const marcado = document.getElementById(`${prefijo}-usa_display`).checked;
        document.getElementById(`${prefijo}-seccion-display`).classList.toggle('hidden', !marcado);
    }

    /**
     * Deja en el select solo los displays que coinciden con el texto del buscador.
     * El que está elegido siempre se queda, para no perder la elección al buscar.
     */
    function filtrarDisplays(prefijo, texto) {
        const select   = document.getElementById(`${prefijo}-display_id`);
        const opciones = OPCIONES_DISPLAY[prefijo];
        const busqueda = texto.trim().toLowerCase();
        const elegido  = select.value;

        const visibles = [opciones.temporal, ...opciones.base].filter(opcion =>
            opcion && (busqueda === '' || opcion.value === elegido || opcion.text.toLowerCase().includes(busqueda))
        );

        select.replaceChildren(opciones.vacia, ...visibles);
        select.value = elegido;
    }

    /**
     * Carga en el modal de edición el display que tiene la reparación.
     * Si ese display ya no está activo, entra como opción temporal "(inactivo)" solo
     * para esta reparación: al abrir otra, se descarta. El display de la reparación se
     * habilita aunque su stock esté en 0, porque esas unidades ya son de ella.
     */
    function prepararDisplayEdicion(datos) {
        const opciones = OPCIONES_DISPLAY.edit;
        const idActual = datos.display_id ? String(datos.display_id) : '';

        opciones.base.forEach(opcion => {
            opcion.disabled = opcion.value !== idActual && (parseInt(opcion.dataset.stock) || 0) < 1;
        });

        opciones.temporal = idActual && !opciones.base.some(opcion => opcion.value === idActual)
            ? new Option(`${datos.display_nombre} (inactivo)`, idActual)
            : null;

        document.getElementById('edit-usa_display').checked  = !!idActual;
        document.getElementById('edit-buscar-display').value = '';
        filtrarDisplays('edit', '');
        document.getElementById('edit-display_id').value       = idActual;
        document.getElementById('edit-display_cantidad').value = datos.display_cantidad || 1;
        toggleDisplay('edit');
    }

    /**
     * Refresca el stock que muestran los selects de displays después de editar.
     * stocks = { productoId: stock, ... } con todas las pantallas activas. Se actualiza
     * la lista guardada, así también quedan al día las opciones que el buscador sacó.
     */
    function actualizarStocksDisplays(stocks) {
        Object.values(OPCIONES_DISPLAY).forEach(({ base }) => {
            base.forEach(opcion => {
                if (!stocks || !(opcion.value in stocks)) return;

                const stock = parseInt(stocks[opcion.value]) || 0;
                opcion.dataset.stock = stock;
                opcion.disabled      = stock < 1;
                opcion.textContent   = `${opcion.dataset.nombre} — stock ${stock}`;
            });
        });
    }

    // ══════════════════════════════════════
    // MODAL DE ABONOS
    // ══════════════════════════════════════
    /**
     * Lee un monto de una celda de la fila ("$90.000" → 90000). Respeta el signo si quedó negativo.
     */
    function leerMonto(fila, celda) {
        return parseInt(fila.querySelector(`[data-celda="${celda}"]`).textContent.replace(/[^\d-]/g, '')) || 0;
    }

    /**
     * Saldo pendiente de la fila: la celda es la única fuente de verdad,
     * porque se actualiza con cada abono y cada edición.
     */
    function leerSaldoDeFila(fila) {
        return leerMonto(fila, 'saldo');
    }

    /**
     * Muestra u oculta los botones "Registrar abono" y "Pagar saldo total" según quede saldo.
     */
    function actualizarBotonesDePago(fila, saldo) {
        fila.querySelector('button[data-btn="abono"]')?.classList.toggle('hidden', saldo <= 0);

        const btnPagarTotal = fila.querySelector('button[data-btn="pagar-total"]');
        if (btnPagarTotal) {
            btnPagarTotal.classList.toggle('hidden', saldo <= 0);
            btnPagarTotal.disabled = false;
        }
    }

    /**
     * Abre el modal de abono. Todo se lee de la fila en el momento del clic
     * (id, nombre y saldo), así nunca queda desactualizado y ningún texto del
     * usuario pasa por el onclick (un nombre como "D'Angelo" lo rompía).
     */
    function abrirModalAbono(boton) {
    const fila  = boton.closest('tr');
    const saldo = leerSaldoDeFila(fila);

    document.getElementById('abono-reparacion-id').value        = fila.dataset.id;
    document.getElementById('abono-cliente-nombre').textContent = fila.querySelector('[data-campo="nombre"]').textContent;
    document.getElementById('abono-saldo-display').textContent  = '$' + saldo.toLocaleString('es-CO');
    document.getElementById('abono-monto').value                = '';
    document.getElementById('abono-monto-visual').value         = '';
    document.getElementById('abono-nota').value                 = '';
    document.getElementById('abono-aviso-cambio').classList.add('hidden');

    pintarBotonAbono(saldo);

    document.getElementById('modal-abono').classList.remove('hidden');
}

    // Botón "Registrar abono" del modal: deshabilitado si ya no queda saldo
    function pintarBotonAbono(saldo) {
        const btn = document.querySelector('#modal-abono button[onclick="guardarAbono()"]');

        btn.disabled = saldo <= 0;
        btn.textContent = saldo <= 0 ? 'Reparación ya pagada' : 'Registrar abono';
        btn.classList.toggle('opacity-50', saldo <= 0);
        btn.classList.toggle('cursor-not-allowed', saldo <= 0);
        btn.classList.toggle('hover:bg-blue-700', saldo > 0);
    }

    function cerrarModalAbono() {
        const id = document.getElementById('abono-reparacion-id').value;

        document.getElementById('modal-abono').classList.add('hidden');
        document.getElementById('abono-reparacion-id').value = '';

        // Si otra persona la cambió de estado mientras estaba abierto, ahora sí sale del filtro
        const fila = document.querySelector(`tr[data-id="${id}"]`);
        if (fila) revisarSalidaDeFiltro(fila);
    }

    // Aplica un pago ya confirmado por el backend sobre la fila de la tabla:
    // escribe el abonado y el saldo, muestra u oculta los botones de pago y mantiene
    // el data-edit al día para que el modal de detalle reabra con cifras correctas.
    // Reutilizada por guardarAbono() (abono parcial) y pagarSaldoTotal() (pago total).
    // Después se llama refrescarFila(): el servidor confirma la fila completa.
    function aplicarPagoEnFila(reparacionId, montoNum, nota, totales = {}) {
    const fila = filaDe(reparacionId);
    if (!fila) return;

    const celdaAbonado = fila.querySelector('[data-celda="abonado"]');
    const celdaSaldo   = fila.querySelector('[data-celda="saldo"]');

    // El servidor manda el abonado y el saldo ya calculados y se escriben tal cual.
    // Sumarle el abono a la celda lo contaría dos veces si el tiempo real ya había
    // puesto la fila al día. (La suma queda solo por si la respuesta no los trae.)
    const nuevoAbonado = Number.isFinite(totales.abonado)
        ? totales.abonado
        : leerMonto(fila, 'abonado') + parseInt(montoNum);
    const nuevoSaldo = Number.isFinite(totales.saldo)
        ? totales.saldo
        : leerMonto(fila, 'valor') - nuevoAbonado;

    celdaAbonado.textContent = '$' + nuevoAbonado.toLocaleString('es-CO');
    celdaSaldo.innerHTML = `<span class="${nuevoSaldo > 0 ? 'text-red-600' : 'text-green-600'} font-medium">$${nuevoSaldo.toLocaleString('es-CO')}</span>`;

    // Ocultar Abono y Pagar total si ya no queda saldo. El modal de abono lee
    // el saldo nuevo de la celda, así que no hace falta tocar su onclick.
    actualizarBotonesDePago(fila, nuevoSaldo);

    // Mantener el data-edit fresco (abonos + saldo) para reaperturas del detalle
    try {
        const datos = JSON.parse(fila.dataset.edit);
        datos.abonos = datos.abonos || [];
        datos.abonos.push({
            monto: parseInt(montoNum),
            nota:  nota || '',
            fecha: new Date().toLocaleString('es-CO', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit', hour12: true,
            }),
        });
        fila.dataset.edit = JSON.stringify(datos);
    } catch (e) { /* data-edit ausente o inválido: la fila igual quedó actualizada */ }
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
            ...cabecerasEnVivo(),
        },
        body: JSON.stringify({ monto, nota })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            cerrarModalAbono();
            aplicarPagoEnFila(reparacionId, monto, nota, data);
            mostrarNotificacion('Abono registrado correctamente');
        } else {
            mostrarNotificacion('Error: ' + data.message, 'error');
        }
        // Si se rechazó porque otra persona ya cobró, el modal abierto muestra el saldo nuevo
        refrescarFila(reparacionId);
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
        const sinFecha = this.value === 'en_proceso';
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
     * Limpia el campo de búsqueda y vuelve a la lista sin búsqueda (mantiene los otros
     * filtros), sin recargar: requestSubmit() pasa por la búsqueda en vivo
     * (resources/js/busqueda-en-vivo.js); submit() recargaría la página.
     * Después deja el foco en el campo, como la barra de búsqueda de iOS: la × desaparece
     * con la búsqueda, y así se puede escribir la próxima sin volver a tocar el campo.
     */
    function limpiarBusqueda() {
        const campo = document.getElementById('input-buscar');

        campo.value = '';
        formFiltros.requestSubmit();
        campo.focus();
    }

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

    // ══════════════════════════════════════
    // TIEMPO REAL (Pusher) — la conexión está en resources/js/tiempo-real.js
    // ══════════════════════════════════════
    // Los avisos de Pusher solo dicen qué reparación cambió: por privacidad no llevan
    // datos del cliente. La fila, los contadores y las cajas libres se piden a
    // /admin/reparaciones/sincronizar, que usa la misma plantilla de la carga normal.
    // Reglas: nada se mueve solo bajo el dedo, no se toca lo que alguien está
    // escribiendo y los avisos flotantes solo salen cuando hay algo que hacer.

    const avisosPendientes = new Map();   // id → avisos que llegaron casi juntos
    let temporizadorAvisos = null;

    // Un cambio de estado trae dos avisos seguidos (estado + WhatsApp): se espera un
    // momento para pedir la fila una sola vez
    window.addEventListener('tiempo-real:reparacion', ({ detail }) => {
        if (!detail || !detail.id) return;

        const id = String(detail.id);
        avisosPendientes.set(id, [...(avisosPendientes.get(id) || []), detail]);

        clearTimeout(temporizadorAvisos);
        temporizadorAvisos = setTimeout(() => {
            const avisos = new Map(avisosPendientes);
            avisosPendientes.clear();
            sincronizarFilas([...avisos.keys()], avisos);
        }, 300);
    });

    // Se creó una caja nueva: solo hace falta la lista de cajas libres
    window.addEventListener('tiempo-real:cajas', () => sincronizarFilas([]));

    // El canal se acaba de suscribir (también después de reconectar), o la tablet volvió
    // del reposo: se pudieron perder avisos. Se pone al día todo lo visible, sin avisos
    // flotantes (son cambios de hace rato).
    window.addEventListener('tiempo-real:ponerse-al-dia', ponerseAlDia);

    // Mantener la sesión viva: cada 30 minutos, mientras la página esté abierta, se pone
    // al día lo visible. Ese pedido renueva la sesión en el servidor (vence a las 2 horas
    // sin actividad) y de paso recoge cualquier aviso perdido. Funciona con o sin Pusher.
    // Si el iPad se bloquea, el temporizador se pausa: si la sesión vence mientras tanto,
    // al volver sale el aviso para recargar.
    setInterval(ponerseAlDia, 30 * 60 * 1000);

    // La sesión venció (lo detecta tiempo-real.js al suscribirse, o sincronizarFilas al
    // pedir filas). Sin sesión la pantalla ya no se pone al día: se avisa una sola vez.
    let avisoSesionMostrado = false;

    window.addEventListener('tiempo-real:sesion-vencida', () => {
        if (avisoSesionMostrado) return;
        avisoSesionMostrado = true;

        mostrarNotificacion(
            'Tu sesión venció. Toca aquí para recargar y volver a ver los cambios de los demás.',
            'warning', 0, { alTocar: () => window.location.reload() }
        );
    });

    // Máximo de filas por pedido: el mismo límite de ReparacionController::sincronizar()
    const MAXIMO_FILAS_POR_PEDIDO = 50;

    /** Pone al día todas las filas visibles, en grupos de 50 (una página trae hasta 25: un solo pedido). */
    function ponerseAlDia() {
        const ids = Array.from(document.querySelectorAll('tr.reparacion-row'), fila => fila.dataset.id);

        // Sin filas igual se piden contadores y cajas libres (y se renueva la sesión)
        if (!ids.length) {
            sincronizarFilas([]);
            return;
        }

        for (let i = 0; i < ids.length; i += MAXIMO_FILAS_POR_PEDIDO) {
            sincronizarFilas(ids.slice(i, i + MAXIMO_FILAS_POR_PEDIDO));
        }
    }

    // Cada pedido a /sincronizar lleva un número, y por fila se guarda el último número
    // que la pidió. Una respuesta más vieja no toca la fila: pudo salir antes de un cambio
    // propio y llegar después. Contadores y cajas, igual: nunca se pintan unos más viejos.
    let numeroPedido = 0;
    let ultimoPedidoPintado = 0;
    const ultimoPedidoPorFila = new Map();   // id → número del último pedido que la incluyó

    // Cuándo se pidió por última vez cada fila (performance.now()): por un aviso de Pusher o
    // después de un cambio propio. Una búsqueda en vivo que salió antes puede traer esas filas
    // en su versión vieja: al llegar, se vuelven a pedir (ver busqueda-en-vivo:actualizada).
    const momentoPedidoPorFila = new Map();  // id → momento

    /**
     * Pide al servidor cómo están ahora esas filas (más contadores y cajas libres) y lo aplica.
     * avisos: Map id → avisos de Pusher que lo causaron (vacío al ponerse al día o al refrescar).
     * opciones.sinAvisosFlotantes: reintento de una fila aplazada (sus avisos ya salieron).
     * Devuelve lo que respondió el servidor ({ filas, contadores, cajas_libres }), o null si no
     * respondió bien.
     */
    async function sincronizarFilas(ids, avisos = new Map(), opciones = {}) {
        const numero  = ++numeroPedido;
        const momento = performance.now();
        ids.forEach(id => {
            ultimoPedidoPorFila.set(String(id), numero);
            momentoPedidoPorFila.set(String(id), momento);
        });

        let data;
        try {
            const respuesta = await fetch(`/admin/reparaciones/sincronizar?ids=${encodeURIComponent(ids.join(','))}`, {
                headers: { 'Accept': 'application/json' },
            });

            // Sin sesión el servidor responde 401 (o 419): se avisa en vez de fallar callado
            const sinSesion = respuesta.status === 401 || respuesta.status === 419
                || (respuesta.redirected && new URL(respuesta.url).pathname === '/login');

            if (sinSesion) {
                window.dispatchEvent(new CustomEvent('tiempo-real:sesion-vencida'));
                return null;
            }
            if (!respuesta.ok) return null;

            data = await respuesta.json();
        } catch (error) {
            // Sin conexión: la pantalla se pone al día sola al reconectar
            return null;
        }

        Object.entries(data.filas || {}).forEach(([id, html]) => {
            if (!html) return;

            // Si la fila ya se pidió otra vez, esta respuesta es vieja: solo salen sus avisos
            const vigente = ultimoPedidoPorFila.get(id) === numero;
            aplicarFilaRemota(id, html, avisos.get(id) || [], { ...opciones, soloAvisos: !vigente });
        });

        if (numero > ultimoPedidoPintado) {
            ultimoPedidoPintado = numero;
            pintarContadores(data.contadores);
            aplicarCajasLibres(data.cajas_libres);
        }

        return data;
    }

    /** Vuelve a pedir una fila después de un cambio propio: el servidor tiene la última palabra. */
    function refrescarFila(id) {
        return sincronizarFilas([String(id)]);
    }

    /** La fila que está en pantalla ahora para esa reparación (el tiempo real pudo reemplazarla). */
    function filaDe(id) {
        return document.querySelector(`tr[data-id="${id}"]`);
    }

    /** Convierte el HTML de una fila que mandó el servidor en un elemento <tr>. */
    function crearFila(html) {
        const plantilla = document.createElement('template');
        plantilla.innerHTML = html.trim();
        return plantilla.content.querySelector('tr');
    }

    function modalEditarAbiertoPara(id) {
        return String(editReparacionId) === String(id)
            && !document.getElementById('modal-editar').classList.contains('hidden');
    }

    function modalAbonoAbiertoPara(id) {
        return document.getElementById('abono-reparacion-id').value === String(id)
            && !document.getElementById('modal-abono').classList.contains('hidden');
    }

    /**
     * Aplica la versión nueva de una fila: la reemplaza, la resalta, pone al día los
     * modales abiertos y la saca del filtro si ya no corresponde. Si la fila no está
     * en esta página, solo salen los avisos flotantes.
     * opciones.soloAvisos: respuesta vieja (la fila ya se pidió otra vez): no se toca.
     * opciones.sinAvisosFlotantes: reintento de una fila aplazada: sus avisos ya salieron.
     */
    function aplicarFilaRemota(id, html, avisos, { soloAvisos = false, sinAvisosFlotantes = false } = {}) {
        const nueva = crearFila(html);
        if (!nueva) return;

        const datos  = JSON.parse(nueva.dataset.edit);
        const autor  = avisos.map(aviso => aviso.autor).filter(Boolean).pop() || null;
        const actual = filaDe(id);

        if (!sinAvisosFlotantes) mostrarAvisosDeOtros(avisos, nueva, datos, Boolean(actual));

        // Respuesta vieja, fila que no está en esta página o que se está yendo del filtro
        if (soloAvisos || !actual || actual.dataset.saliendo) return;

        // Alguien está usando un control de esa fila (por ejemplo, el selector de estado
        // abierto en la tablet): se espera a que termine. Un botón con foco no cuenta: en
        // el PC, Editar o Abono se quedan con el foco detrás del modal.
        const enUso = actual.contains(document.activeElement)
            && document.activeElement.matches('select, input, textarea');

        if (enUso) {
            aplazarFila(id, avisos, actual);
            return;
        }

        actual.replaceWith(nueva);
        pintarWhatsapp(nueva);

        if (avisos.length) resaltarFila(nueva, autor);
        ponerAlDiaModalesAbiertos(id, nueva, datos, avisos, autor);
        revisarSalidaDeFiltro(nueva);
    }

    const avisosAplazados = new Map();   // id → { fila, avisos } de una fila que se estaba usando

    /**
     * Espera a que se deje de usar la fila y la vuelve a pedir. El reintento conserva quién
     * hizo el cambio (resaltado y aviso del modal) sin repetir los avisos flotantes.
     */
    function aplazarFila(id, avisos, fila) {
        const esperando = avisosAplazados.get(id);
        if (esperando && esperando.fila === fila) {
            esperando.avisos.push(...avisos);   // ya hay un reintento esperando esa fila
            return;
        }

        avisosAplazados.set(id, { fila, avisos: [...avisos] });

        fila.addEventListener('focusout', () => setTimeout(() => {
            const pendientes = avisosAplazados.get(id)?.avisos || [];
            avisosAplazados.delete(id);
            sincronizarFilas([id], new Map([[id, pendientes]]), { sinAvisosFlotantes: true });
        }, 0), { once: true });
    }

    /** Fondo amarillo suave por 2 s y, al pasar el cursor, quién hizo el cambio. */
    function resaltarFila(fila, autor = null) {
        if (autor) fila.title = `Último cambio: ${autor}`;

        fila.style.transition      = 'background-color 0.6s ease';
        fila.style.backgroundColor = '#fef9c3';
        setTimeout(() => { fila.style.backgroundColor = ''; }, 2000);
    }

    /**
     * Avisos flotantes por cambios de otras personas. Solo dos, porque piden hacer algo:
     * una reparación quedó lista, o el WhatsApp al cliente no llegó (este se queda).
     */
    function mostrarAvisosDeOtros(avisos, fila, datos, estaEnPagina) {
        const equipo   = [datos.marca, datos.modelo].filter(Boolean).join(' ');
        const opciones = estaEnPagina ? { alTocar: () => irAFila(datos.id) } : {};

        avisos
            .filter(aviso => aviso.accion === 'estado' && aviso.estado === 'arreglado')
            .forEach(aviso => mostrarNotificacion(
                `Lista: ${equipo} de ${datos.nombre_cliente}${aviso.autor ? ` · por ${aviso.autor}` : ''}`,
                'success', 6000, opciones
            ));

        const whatsapp = fila.dataset.whatsapp ? JSON.parse(fila.dataset.whatsapp) : null;
        const noLlego  = avisos.some(aviso => aviso.accion === 'whatsapp')
            && whatsapp?.estado === 'fallido'
            && fila.dataset.estado === 'arreglado';

        if (noLlego) {
            const telefono = datos.telefono_cliente ? ` (${datos.telefono_cliente})` : '';
            mostrarNotificacion(
                `No le llegó el WhatsApp a ${datos.nombre_cliente}${telefono}. Llámalo. Motivo: ${whatsapp.motivo || 'desconocido'}`,
                'error', 0, opciones
            );
        }
    }

    function irAFila(id) {
        const fila = document.querySelector(`tr[data-id="${id}"]`);
        if (!fila) return;

        fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
        resaltarFila(fila);
    }

    /** Los campos editables como texto, igual que Reparacion::huellaEdicion() en PHP. */
    function huellaEdicion(datos) {
        return CAMPOS_EDITABLES.map(campo => {
            const valor = datos?.[campo];
            return typeof valor === 'boolean' ? (valor ? '1' : '0') : String(valor ?? '').trim();
        }).join('|');
    }

    /**
     * Modales abiertos sobre esa reparación:
     * - Editar: no se tocan los campos que se están escribiendo. Si otra persona cambió
     *   alguno de los campos editables, se avisa y se ofrece cargar sus cambios (si se
     *   guarda sin cargarlos, el servidor lo rechaza igual).
     * - Abono: el saldo se pone al día y se dice por qué cambió.
     */
    function ponerAlDiaModalesAbiertos(id, fila, datos, avisos, autor) {
        const quien = autor || 'Otra persona';

        if (modalEditarAbiertoPara(id) && datosAlAbrir && huellaEdicion(datosAlAbrir) !== huellaEdicion(datos)) {
            mostrarAvisoCambioEdicion(`${quien} acaba de cambiar esta reparación.`);
        }

        if (modalAbonoAbiertoPara(id)) {
            const saldo   = leerSaldoDeFila(fila);
            const visible = document.getElementById('abono-saldo-display');
            const nuevo   = '$' + saldo.toLocaleString('es-CO');

            if (visible.textContent !== nuevo) {
                const aviso = document.getElementById('abono-aviso-cambio');
                aviso.textContent = avisos.some(a => a.accion === 'abono')
                    ? `${quien} registró un abono. Saldo ahora: ${nuevo}.`
                    : `${quien} cambió esta reparación. Saldo ahora: ${nuevo}.`;
                aviso.classList.remove('hidden');

                visible.textContent = nuevo;
                pintarBotonAbono(saldo);
            }
        }
    }

    /**
     * Si la fila ya no corresponde al filtro que se está viendo (ej: se ve "En proceso"
     * y otra persona la pasó a "Arreglado"), se va despacio a los 3 s. Si tiene un modal
     * abierto, se queda hasta que lo cierren (cerrarModalEditar / cerrarModalAbono).
     */
    function revisarSalidaDeFiltro(fila) {
        const filtro = document.getElementById('select-estado')?.value
            || new URLSearchParams(window.location.search).get('estado');

        if (!filtro || filtro === 'todos' || fila.dataset.estado === filtro) return;

        setTimeout(() => {
            const actual = filaDe(fila.dataset.id);
            if (!actual || actual.dataset.saliendo || actual.dataset.estado === filtro) return;
            if (modalEditarAbiertoPara(actual.dataset.id) || modalAbonoAbiertoPara(actual.dataset.id)) return;

            desvanecerFila(actual);
        }, 3000);
    }

    /**
     * Pone al día las cajas del formulario de nueva reparación. Si la caja elegida se
     * acaba de ocupar, se quita la selección y se avisa debajo del campo, antes de guardar.
     */
    function aplicarCajasLibres(libres) {
        const select = document.querySelector('#modal-nueva select[name="caja_id"]');
        if (!select || !Array.isArray(libres)) return;

        const elegida       = select.value;
        const nombreElegida = select.selectedOptions[0]?.textContent.trim();
        const sigueLibre    = libres.some(caja => String(caja.id) === elegida);

        select.replaceChildren(select.options[0], ...libres.map(caja => new Option(caja.nombre, caja.id)));
        select.value = sigueLibre ? elegida : '';

        if (elegida && !sigueLibre) {
            const aviso = document.getElementById('aviso-caja-ocupada');
            aviso.textContent = `La caja ${nombreElegida} se acaba de ocupar. Elige otra.`;
            aviso.classList.remove('hidden');
        }
    }

    // Si el formulario de crear volvió con errores de validación, los montos llegan
    // en los campos ocultos (old()); se muestran también en los visibles.
    [
        ['valor_total_visual',     'valor_total'],
        ['costo_repuestos_visual', 'costo_repuestos'],
        ['abono_inicial_visual',   'abono_inicial'],
    ].forEach(([visual, oculto]) => {
        const valor = parseInt(document.getElementById(oculto)?.value);
        if (valor) document.getElementById(visual).value = valor.toLocaleString('es-CO');
    });

    // Pintar el indicador de WhatsApp de cada fila al cargar la página, y a las filas que
    // trae la búsqueda en vivo. El tiempo real trabaja sobre las filas que estén en
    // pantalla en cada momento (filaDe, ponerseAlDia).
    document.querySelectorAll('tr.reparacion-row').forEach(pintarWhatsapp);

    // La búsqueda en vivo se armó en el servidor cuando salió su pedido (detail.inicio). Las
    // filas que se pidieron a /sincronizar desde entonces (un aviso de Pusher, un cambio
    // propio) pueden venir en su versión de antes: se vuelven a pedir, sin avisos ni
    // resaltado. Sin esto, una reparación que otra persona marcó «Arreglado» mientras
    // llegaba la búsqueda volvía a verse «En proceso».
    document.addEventListener('busqueda-en-vivo:actualizada', ({ detail }) => {
        document.querySelectorAll('tr.reparacion-row').forEach(pintarWhatsapp);

        const pedidasMientras = [...momentoPedidoPorFila]
            .filter(([id, momento]) => momento >= detail.inicio && filaDe(id))
            .map(([id]) => id);

        if (pedidasMientras.length) {
            sincronizarFilas(pedidasMientras, new Map(), { sinAvisosFlotantes: true });
        }
    });

    // Abrir el modal correcto según el bag de errores de validación
    @if ($errors->getBag('default')->any())
        document.getElementById('modal-nueva').classList.remove('hidden');
    @endif
    @if ($errors->getBag('caja')->any())
        document.getElementById('modal-nueva-caja').classList.remove('hidden');
    @endif
</script>
@endsection
