{{--
    Vista de edición de una reparación existente.

    Muestra:
    - Info de solo lectura (caja, estado, fechas, saldos)
    - Formulario editable (cliente, equipo, valores)

    Lo que NO se puede editar desde aquí:
    - caja_id  → cambiarla requiere liberar/ocupar cajas (otra funcionalidad)
    - estado   → se cambia con el select AJAX en el listado
    - abonos   → tienen su propio modal en el listado
--}}
@extends('layouts.admin')

@section('page-title', 'Editar reparación')

@section('content')

{{-- Mensajes flash --}}
@if(session('error'))
    <div id="alert-error" class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('error') }}
    </div>
@endif

{{-- Header de página --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Editar reparación</h2>
        <p class="text-gray-500 mt-1">Modifica los datos de la reparación</p>
    </div>
    {{-- Botón volver al listado --}}
    <a href="{{ route('admin.reparaciones.index') }}"
       class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Volver al listado
    </a>
</div>

<div class="max-w-3xl">

    {{-- ========================================= --}}
    {{-- CARD PRINCIPAL                             --}}
    {{-- ========================================= --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">

        {{-- Header de la card: título + badge de estado --}}
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">
                    Reparación #{{ $reparacion->id }}
                </h3>
                <p class="text-sm text-gray-500 mt-0.5">
                    Creada el {{ $reparacion->created_at->format('d/m/Y \a \l\a\s h:i A') }}
                </p>
            </div>

            {{-- Badge del estado actual — solo lectura, se cambia desde el listado --}}
            @php
                // Mapeamos el valor del enum al estilo Tailwind correspondiente
                $badgeClase = match($reparacion->estado) {
                    \App\Enums\EstadoReparacion::EnProceso => 'bg-amber-50 text-amber-700 border border-amber-200',
                    \App\Enums\EstadoReparacion::Arreglado => 'bg-blue-50 text-blue-700 border border-blue-200',
                    \App\Enums\EstadoReparacion::Entregado => 'bg-green-50 text-green-700 border border-green-200',
                };
            @endphp
            <span class="text-xs font-semibold px-3 py-1.5 rounded-lg {{ $badgeClase }}">
                {{ $reparacion->estado->label() }}
            </span>
        </div>

        {{-- ========================================= --}}
        {{-- BLOQUE DE SOLO LECTURA                    --}}
        {{-- Info que no se puede editar desde aquí    --}}
        {{-- ========================================= --}}
        <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
                Información de solo lectura
            </p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

                {{-- Caja asignada --}}
                <div>
                    <p class="text-xs text-gray-400 mb-1">Caja</p>
                    <span class="inline-block bg-gray-100 text-gray-700 text-xs font-bold px-2.5 py-1 rounded-lg">
                        {{ $reparacion->caja->nombre_display }}
                    </span>
                </div>

                {{-- Total abonado (calculado por accessor del modelo) --}}
                <div>
                    <p class="text-xs text-gray-400 mb-1">Total abonado</p>
                    <p class="text-sm font-semibold text-gray-700">
                        ${{ number_format($reparacion->total_abonado, 0, ',', '.') }}
                    </p>
                </div>

                {{-- Saldo pendiente (calculado por accessor del modelo) --}}
                <div>
                    <p class="text-xs text-gray-400 mb-1">Saldo pendiente</p>
                    @php $saldo = $reparacion->saldo_pendiente; @endphp
                    <p class="text-sm font-semibold {{ $saldo > 0 ? 'text-red-600' : 'text-green-600' }}">
                        ${{ number_format($saldo, 0, ',', '.') }}
                    </p>
                </div>

                {{-- Fecha de entrega, si existe --}}
                <div>
                    <p class="text-xs text-gray-400 mb-1">Fecha entrega</p>
                    <p class="text-sm text-gray-700">
                        {{ $reparacion->fecha_entrega?->format('d/m/Y') ?? '—' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- ========================================= --}}
        {{-- FORMULARIO EDITABLE                        --}}
        {{-- ========================================= --}}
        {{--
            method POST con @method('PUT'):
            Los navegadores solo entienden GET y POST en formularios HTML.
            @method('PUT') agrega un campo oculto _method=PUT que Laravel
            intercepta para rutear al método update() del controlador.
        --}}
        <form method="POST"
              action="{{ route('admin.reparaciones.update', $reparacion) }}"
              class="p-6 space-y-6">
            @csrf
            @method('PUT')

            {{-- Errores de validación (aparecen si update() hace withInput()) --}}
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
                    <p class="font-semibold mb-1">Por favor corrige los siguientes errores:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ── Sección: Datos del cliente ── --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">
                    Datos del cliente
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Nombre *</label>
                        <input type="text"
                               name="nombre_cliente"
                               value="{{ old('nombre_cliente', $reparacion->nombre_cliente) }}"
                               placeholder="Juan Pérez"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition @error('nombre_cliente') border-red-400 @enderror">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Teléfono</label>
                        <input type="text"
                               name="telefono_cliente"
                               value="{{ old('telefono_cliente', $reparacion->telefono_cliente) }}"
                               placeholder="310 123 4567"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition @error('telefono_cliente') border-red-400 @enderror">
                    </div>
                </div>
            </div>

            {{-- ── Sección: Datos del equipo ── --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">
                    Datos del equipo
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Marca *</label>
                        <input type="text"
                               name="marca"
                               value="{{ old('marca', $reparacion->marca) }}"
                               placeholder="Samsung, iPhone..."
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition @error('marca') border-red-400 @enderror">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Modelo</label>
                        <input type="text"
                               name="modelo"
                               value="{{ old('modelo', $reparacion->modelo) }}"
                               placeholder="Galaxy S24..."
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="text-xs text-gray-500 mb-1 block">Descripción de la falla</label>
                    <textarea name="descripcion_falla"
                              rows="3"
                              placeholder="¿Qué le pasa al equipo?"
                              class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition resize-none">{{ old('descripcion_falla', $reparacion->descripcion_falla) }}</textarea>
                </div>
            </div>

            {{-- ── Sección: Valores económicos ── --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">
                    Valores
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Valor total ──────────────────────────────────────────
                         Usamos el mismo patrón que el modal de crear:
                         - input visible (tipo text) → muestra el número formateado con puntos de miles
                         - input hidden              → guarda el número limpio para el servidor

                         Al cargar la página, pre-calculamos el valor visual desde PHP
                         para que el campo aparezca ya formateado (ej: "150.000").
                         Si hubo error de validación, old() devuelve el valor numérico
                         limpio y también lo formateamos.
                    --}}
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Valor total *</label>
                        @php
                            // Calculamos el valor a mostrar en el input visual:
                            // old() tiene prioridad (error de validación), si no, usamos el de la BD.
                            $valorTotalRaw     = old('valor_total', (int) $reparacion->valor_total);
                            $valorTotalDisplay = $valorTotalRaw ? number_format((int) $valorTotalRaw, 0, ',', '.') : '';
                        @endphp
                        <input type="text"
                               id="valor_total_visual"
                               value="{{ $valorTotalDisplay }}"
                               placeholder="150.000"
                               inputmode="numeric"
                               oninput="formatearPrecio(this, 'valor_total')"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition @error('valor_total') border-red-400 @enderror">
                        {{-- El campo hidden es el que realmente se envía al servidor --}}
                        <input type="hidden" name="valor_total" id="valor_total" value="{{ $valorTotalRaw }}">
                    </div>

                    {{-- Costo de repuestos (mismo patrón que valor total) --}}
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Costo de repuestos</label>
                        @php
                            $costoRepuestosRaw     = old('costo_repuestos', (int) $reparacion->costo_repuestos);
                            $costoRepuestosDisplay = $costoRepuestosRaw ? number_format((int) $costoRepuestosRaw, 0, ',', '.') : '';
                        @endphp
                        <input type="text"
                               id="costo_repuestos_visual"
                               value="{{ $costoRepuestosDisplay }}"
                               placeholder="50.000"
                               inputmode="numeric"
                               oninput="formatearPrecio(this, 'costo_repuestos')"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition @error('costo_repuestos') border-red-400 @enderror">
                        <input type="hidden" name="costo_repuestos" id="costo_repuestos" value="{{ $costoRepuestosRaw }}">
                    </div>
                </div>
            </div>

            {{-- ── Botones de acción ── --}}
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <a href="{{ route('admin.reparaciones.index') }}"
                   class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-sm shadow-blue-600/20">
                    Guardar cambios
                </button>
            </div>

        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // ══════════════════════════════════════
    // FORMATO DE MONEDA COLOMBIANA
    // ══════════════════════════════════════

    /**
     * Formatea el input visible con puntos de miles y guarda el número
     * limpio en el campo hidden que se envía al servidor.
     *
     * Es la misma función del modal de crear — se copia aquí porque
     * esta es una página independiente y no comparte el script del index.
     */
    function formatearPrecio(input, hiddenId) {
        // Eliminamos todo lo que no sea dígito
        let valor = input.value.replace(/\D/g, '');

        // Guardamos el número limpio en el hidden (lo que recibe el servidor)
        document.getElementById(hiddenId).value = valor;

        // Mostramos el número formateado con puntos de miles al usuario
        if (valor) {
            input.value = parseInt(valor).toLocaleString('es-CO');
        }
    }

    // ── Auto-ocultar alertas flash ──
    // Las alertas desaparecen solas después de 4 segundos con un fundido suave
    setTimeout(() => {
        document.querySelectorAll('[id^="alert-"]').forEach(el => {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity    = '0';
            setTimeout(() => el.remove(), 500);
        });
    }, 4000);
</script>
@endsection
