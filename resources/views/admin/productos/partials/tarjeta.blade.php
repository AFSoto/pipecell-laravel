{{-- Tarjeta de un producto (vista de tarjetas): imagen grande, datos, precio, stock y acciones.
     id: al volver de editar, la página baja hasta aquí (#producto-{id}) y la tarjeta se
     resalta con un anillo (target:); scroll-mt-24 evita que la tape el encabezado fijo.
     flex-col + flex-1: los botones quedan abajo, alineados con los de la tarjeta de al lado
     aunque una tenga etiquetas o código y la otra no. --}}
<div id="producto-{{ $producto->id }}"
     class="scroll-mt-24 bg-white rounded-2xl border border-gray-100 hover:shadow-lg target:ring-2 target:ring-blue-400 transition overflow-hidden group flex flex-col">

    {{-- Imagen del producto (carga cuando aparece en pantalla) --}}
    <div class="relative aspect-square bg-gray-50">
        @if ($producto->imagenPrincipal)
            <img src="{{ asset('storage/' . $producto->imagenPrincipal->path) }}"
                 alt="{{ $producto->nombre }}"
                 loading="lazy" decoding="async"
                 class="w-full h-full object-cover">
        @else
            {{-- Placeholder cuando no hay imagen --}}
            <div class="w-full h-full flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif

        {{-- Badge de categoría en la esquina superior --}}
        <span class="absolute top-2 left-2 text-xs font-medium px-2 py-0.5 bg-white/90 text-gray-600 rounded-full shadow-sm">
            {{ $producto->categoria->nombre }}
        </span>
    </div>

    {{-- Información del producto: crece (flex-1) para que los botones queden abajo --}}
    <div class="p-4 flex-1 flex flex-col">
        <div class="flex-1">
            <h3 class="font-semibold text-gray-900 text-sm leading-snug line-clamp-1">
                {{ $producto->nombre }}
            </h3>

            @if ($producto->codigo)
                <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $producto->codigo }}</p>
            @endif

            {{-- Etiquetas de marca, tipo y marco --}}
            @if ($producto->marca_visible !== null || $producto->tipoProducto || $producto->marco !== null)
                <div class="flex flex-wrap gap-1 mt-1.5">
                    @include('admin.productos.partials.etiquetas', ['producto' => $producto])
                </div>
            @endif

            {{-- Precio de venta --}}
            <p class="text-base font-bold text-gray-900 mt-2">
                ${{ number_format($producto->precio_venta, 0, ',', '.') }}
            </p>

            {{-- Precio de compra tachado (referencia de costo).
                 Es opcional: si el producto no tiene, no se muestra (ni "$0"). --}}
            @if ($producto->precio_compra !== null)
                <p class="text-xs text-gray-400 line-through">
                    ${{ number_format($producto->precio_compra, 0, ',', '.') }}
                </p>
            @endif

            {{-- Stock con semáforo de color (clickeable para ajuste rápido) --}}
            @include('admin.productos.partials.stock', ['producto' => $producto, 'margen' => 'mt-2'])
        </div>

        {{-- Botones de acción --}}
        <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-50">
            @include('admin.productos.partials.acciones', ['producto' => $producto])
        </div>
    </div>
</div>
