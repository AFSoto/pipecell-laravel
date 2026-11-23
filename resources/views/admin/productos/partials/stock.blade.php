{{--
    Stock con semáforo de color; al tocarlo abre el ajuste rápido.

    Lo usan la tarjeta y la fila de la lista. actualizarBadgeStock() lo busca por id
    y cambia el <span> después de ajustar: solo se dibuja una vista, así el id no se repite.

    $margen: espacio arriba (en la tarjeta va debajo del precio).
    En pantallas táctiles (el iPad) la zona para tocar mide 44 px de alto.
--}}
@php
    if ($producto->stock === 0) {
        $stockColor = 'text-red-600';
        $stockLabel = 'Sin stock';
    } elseif ($producto->stock < $producto->stock_minimo) {
        $stockColor = 'text-red-600';
        $stockLabel = $producto->stock . ' ' . ($producto->stock === 1 ? 'unidad' : 'unidades');
    } elseif ($producto->stock == $producto->stock_minimo) {
        $stockColor = 'text-amber-600';
        $stockLabel = $producto->stock . ' ' . ($producto->stock === 1 ? 'unidad' : 'unidades');
    } else {
        $stockColor = 'text-green-600';
        $stockLabel = $producto->stock . ' ' . ($producto->stock === 1 ? 'unidad' : 'unidades');
    }
@endphp

<button id="stock-display-{{ $producto->id }}"
        onclick="abrirAjusteStock({{ $producto->id }}, @js($producto->nombre))"
        class="group flex items-center gap-1.5 {{ $margen ?? '' }} pointer-coarse:min-h-11 pointer-coarse:pr-2 hover:opacity-75 transition text-left"
        title="Ajustar stock">
    <span class="text-xs font-medium {{ $stockColor }}">{{ $stockLabel }}</span>
    {{-- El lápiz aparece al pasar el mouse; en pantallas táctiles (sin hover) se ve siempre --}}
    <svg class="w-3 h-3 text-gray-300 opacity-0 group-hover:opacity-100 pointer-coarse:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
    </svg>
</button>
