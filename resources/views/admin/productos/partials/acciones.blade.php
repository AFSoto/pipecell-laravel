{{--
    Botones de cada producto: Editar, Ajustar stock y Desactivar.
    Solo los botones: la tarjeta y la fila ponen el contenedor.

    $compacto = true en la fila de la lista (solo íconos); en la tarjeta, Editar lleva texto.
    @js() escapa comillas y saltos de línea para JavaScript, y da null cuando no hay valor
    (el precio de compra es opcional: con {{ }} quedaba ", ," y Editar no funcionaba).

    En pantallas táctiles (pointer-coarse: el iPad) los botones crecen a 44 px, lo que
    pide Apple para tocar sin errar; con mouse quedan de 32 px.
--}}
@php
    $compacto    = $compacto ?? false;
    $claseEditar = $compacto
        ? 'p-2 pointer-coarse:p-3.5 text-gray-400 hover:bg-blue-50 hover:text-blue-600 rounded-xl transition'
        : 'flex-1 flex items-center justify-center gap-1.5 py-2 pointer-coarse:py-3 text-xs font-medium text-gray-600 bg-gray-50 hover:bg-blue-50 hover:text-blue-600 rounded-xl transition';
@endphp

{{-- Editar --}}
<button onclick="abrirEditarProducto(
            {{ $producto->id }},
            {{ $producto->categoria_id }},
            {{ $producto->tipo_producto_id ?? 'null' }},
            @js($producto->codigo ?? ''),
            @js($producto->nombre),
            @js($producto->descripcion ?? ''),
            @js($producto->precio_compra),
            {{ $producto->precio_venta }},
            {{ $producto->stock }},
            {{ $producto->stock_minimo }},
            {{ is_null($producto->marco) ? 'null' : ($producto->marco ? '1' : '0') }},
            @js($producto->marca ?? ''),
            @js($producto->referencia ?? ''),
            {{ $producto->imagenes->map(fn($i) => ['id' => $i->id, 'path' => $i->path, 'es_principal' => $i->es_principal])->toJson() }}
        )"
        class="{{ $claseEditar }}"
        @if ($compacto) title="Editar" aria-label="Editar" @endif>
    <svg class="{{ $compacto ? 'w-4 h-4' : 'w-3.5 h-3.5' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
    </svg>
    @unless ($compacto)
        Editar
    @endunless
</button>

{{-- Ajustar stock --}}
<button onclick="abrirAjusteStock({{ $producto->id }}, @js($producto->nombre))"
        class="p-2 pointer-coarse:p-3.5 text-gray-400 hover:bg-emerald-50 hover:text-emerald-600 rounded-xl transition"
        title="Ajustar stock" aria-label="Ajustar stock">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
    </svg>
</button>

{{-- Desactivar --}}
<form method="POST"
      action="{{ route('admin.productos.destroy', $producto) }}"
      onsubmit="return confirm(@js('¿Desactivar «' . $producto->nombre . '»?'))">
    @csrf
    @method('DELETE')
    {{-- La query de esta página: después de desactivar se vuelve a la misma lista --}}
    <input type="hidden" name="volver" value="{{ request()->getQueryString() }}">
    <button type="submit"
            class="p-2 pointer-coarse:p-3.5 text-gray-400 hover:bg-red-50 hover:text-red-600 rounded-xl transition"
            title="Desactivar" aria-label="Desactivar">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>
    </button>
</form>
