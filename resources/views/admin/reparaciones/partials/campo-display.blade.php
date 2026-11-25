{{--
    Sección "¿Se usó display?" de los modales de crear y editar reparación.

    Parámetros:
      $prefijo  'nueva' | 'edit'  → prefijo de los IDs (lo usan las funciones JS)
      $conOld   bool              → repoblar con old() tras un error de validación (solo crear)
      $displays Collection        → pantallas activas {id, nombre, detalle, stock}
                                    (ReparacionController::displaysParaElBuscador)

    El campo "Display" es el buscador: se escribe marca, modelo, tipo o marco y se toca uno
    de la lista (la arma el JS de reparaciones/index, sección DISPLAY USADO). El elegido va
    en el oculto display_id, que es lo que se guarda.

    Al guardar, el servidor descuenta el stock y registra el movimiento en Inventario.
    No toca el costo de Repuestos: el valor total ya incluye repuestos y mano de obra.
--}}
@php
    $usaOld      = $conOld && old('usa_display');
    $cantidadOld = $conOld ? old('display_cantidad', 1) : 1;

    // Después de un error al crear vuelve el display elegido, si sigue en la lista. El texto
    // es el mismo que pone el JS al elegir (textoDelDisplay): nombre y detalle
    $idOld      = $conOld ? old('display_id') : null;
    $elegidoOld = is_numeric($idOld) ? $displays->firstWhere('id', (int) $idOld) : null;
    $textoOld   = $elegidoOld
        ? $elegidoOld['nombre'] . ($elegidoOld['detalle'] !== '' ? ' · ' . $elegidoOld['detalle'] : '')
        : '';
@endphp

<div class="rounded-xl border border-gray-200 p-4 space-y-3">
    <input type="hidden" name="usa_display" value="0">
    <label class="flex items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer select-none">
        <input type="checkbox" name="usa_display" value="1" id="{{ $prefijo }}-usa_display"
               onchange="toggleDisplay(@js($prefijo), true)" {{ $usaOld ? 'checked' : '' }}
               class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
        ¿Se usó display?
    </label>

    <div id="{{ $prefijo }}-seccion-display" class="{{ $usaOld ? '' : 'hidden' }} space-y-3">
        @if($displays->isEmpty())
            <p class="text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-2">
                No hay pantallas activas en el inventario. Agrégalas en Productos, en una categoría
                cuyo nombre contenga "pantalla" (por ejemplo "Repuestos - Pantallas").
            </p>
        @endif

        <div class="grid grid-cols-3 gap-3">
            <div class="col-span-2">
                <label for="{{ $prefijo }}-buscar-display" class="text-xs text-gray-500 mb-1 block">Display *</label>
                <div class="relative" data-buscador-display="{{ $prefijo }}">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" id="{{ $prefijo }}-buscar-display" value="{{ $textoOld }}"
                           placeholder="Busca por marca o modelo..."
                           role="combobox" aria-autocomplete="list" aria-expanded="false"
                           aria-controls="{{ $prefijo }}-lista-displays"
                           autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" enterkeyhint="done"
                           class="w-full pl-9 pr-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    <input type="hidden" name="display_id" id="{{ $prefijo }}-display_id" value="{{ $elegidoOld['id'] ?? '' }}">

                    {{-- La lista de resultados, flotando bajo el campo --}}
                    <div id="{{ $prefijo }}-desplegable-display"
                         class="hidden absolute z-20 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
                        <ul id="{{ $prefijo }}-lista-displays" role="listbox" aria-label="Displays"
                            class="max-h-60 overflow-y-auto overscroll-contain py-1"></ul>
                        <p id="{{ $prefijo }}-aviso-displays" role="status"
                           class="hidden px-3 py-2 text-xs text-gray-500 border-t border-gray-100"></p>
                    </div>
                </div>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Cantidad</label>
                <input type="number" name="display_cantidad" id="{{ $prefijo }}-display_cantidad"
                       min="1" max="20" value="{{ $cantidadOld }}"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>
        </div>

        <p class="text-xs text-gray-400">
            Se descuenta del inventario al guardar.
        </p>
    </div>
</div>
