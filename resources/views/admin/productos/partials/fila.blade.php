{{-- Fila de un producto (vista de lista): miniatura, datos, precio, stock y acciones.
     id: al volver de editar, la página baja hasta aquí (#producto-{id}) y la fila se
     resalta (target:); scroll-mt-24 evita que la tape el encabezado fijo. --}}
<tr id="producto-{{ $producto->id }}" class="scroll-mt-24 hover:bg-gray-50/70 target:bg-blue-50 transition">

    {{-- Miniatura: tamaño fijo para que la fila no salte; carga cuando aparece en pantalla --}}
    <td class="py-2 pl-4 pr-2 w-16">
        @if ($producto->imagenPrincipal)
            <img src="{{ asset('storage/' . $producto->imagenPrincipal->path) }}"
                 alt="" width="48" height="48" loading="lazy" decoding="async"
                 class="w-12 h-12 max-w-none rounded-lg object-cover bg-gray-50">
        @else
            <div class="w-12 h-12 rounded-lg bg-gray-50 flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif
    </td>

    {{-- Producto: nombre, código y etiquetas (en pantallas chicas, también la categoría) --}}
    <td class="py-2 px-4 min-w-[14rem]">
        <p class="font-semibold text-gray-900 leading-snug line-clamp-2">{{ $producto->nombre }}</p>
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">
            @if ($producto->codigo)
                <span class="text-xs text-gray-400 font-mono">{{ $producto->codigo }}</span>
            @endif
            <span class="lg:hidden text-xs text-gray-400">{{ $producto->categoria->nombre }}</span>
            @include('admin.productos.partials.etiquetas', ['producto' => $producto])
        </div>
    </td>

    <td class="hidden lg:table-cell py-2 px-4 text-gray-600 whitespace-nowrap">
        {{ $producto->categoria->nombre }}
    </td>

    {{-- Precio de venta y, debajo, el de compra tachado (referencia de costo) --}}
    <td class="py-2 px-4 text-right whitespace-nowrap">
        <p class="font-bold text-gray-900">${{ number_format($producto->precio_venta, 0, ',', '.') }}</p>
        <p class="text-xs text-gray-400 line-through">${{ number_format($producto->precio_compra, 0, ',', '.') }}</p>
    </td>

    {{-- Stock con semáforo de color (clickeable para ajuste rápido) --}}
    <td class="py-2 px-4 whitespace-nowrap">
        @include('admin.productos.partials.stock', ['producto' => $producto])
    </td>

    <td class="py-2 pl-4 pr-3">
        <div class="flex items-center justify-end gap-1 pointer-coarse:gap-2">
            @include('admin.productos.partials.acciones', ['producto' => $producto, 'compacto' => true])
        </div>
    </td>
</tr>
