{{--
    Sección "¿Se usó display?" de los modales de crear y editar reparación.

    Parámetros:
      $prefijo  'nueva' | 'edit'  → prefijo de los IDs (lo usan las funciones JS)
      $conOld   bool              → repoblar con old() tras un error de validación (solo crear)
      $displays Collection        → pantallas activas (id, nombre, stock, precio_compra)

    Al guardar, el servidor descuenta el stock y registra el movimiento en Inventario.
--}}
@php
    $usaOld      = $conOld && old('usa_display');
    $displayOld  = $conOld ? (string) old('display_id') : '';
    $cantidadOld = $conOld ? old('display_cantidad', 1) : 1;
@endphp

<div class="rounded-xl border border-gray-200 p-4 space-y-3">
    <input type="hidden" name="usa_display" value="0">
    <label class="flex items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer select-none">
        <input type="checkbox" name="usa_display" value="1" id="{{ $prefijo }}-usa_display"
               onchange="toggleDisplay('{{ $prefijo }}')" {{ $usaOld ? 'checked' : '' }}
               class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
        ¿Se usó display?
    </label>

    <div id="{{ $prefijo }}-seccion-display" class="{{ $usaOld ? '' : 'hidden' }} space-y-3">
        @if($displays->isEmpty())
            <p class="text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-2">
                No hay pantallas activas en el inventario. Agrégalas en Productos (categoría de pantallas).
            </p>
        @endif

        <input type="text" id="{{ $prefijo }}-buscar-display" placeholder="Buscar display (marca, modelo...)"
               oninput="filtrarDisplays('{{ $prefijo }}', this.value)"
               class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">

        <div class="grid grid-cols-3 gap-3">
            <div class="col-span-2">
                <label class="text-xs text-gray-500 mb-1 block">Display *</label>
                <select name="display_id" id="{{ $prefijo }}-display_id" onchange="calcularCostoDisplay('{{ $prefijo }}')"
                        class="select-display w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    <option value="">Seleccionar display...</option>
                    @foreach($displays as $display)
                        <option value="{{ $display->id }}"
                                data-nombre="{{ $display->nombre }}"
                                data-precio="{{ (int) $display->precio_compra }}"
                                data-stock="{{ $display->stock }}"
                                {{ $display->stock < 1 ? 'disabled' : '' }}
                                {{ $displayOld === (string) $display->id ? 'selected' : '' }}>
                            {{ $display->nombre }} — stock {{ $display->stock }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Cantidad</label>
                <input type="number" name="display_cantidad" id="{{ $prefijo }}-display_cantidad"
                       min="1" max="20" value="{{ $cantidadOld }}"
                       oninput="calcularCostoDisplay('{{ $prefijo }}')"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>
        </div>

        <p class="text-xs text-gray-400">
            Se descuenta del inventario al guardar. Su costo se pone en "Repuestos" y lo puedes cambiar.
        </p>
    </div>
</div>
