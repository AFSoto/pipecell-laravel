{{--
    Vista de gestión de productos del inventario.

    Incluye:
    - Filtros por categoría, búsqueda de texto y stock bajo
    - Grid de cards con imagen, precio, stock y acciones
    - Modal para crear producto (con subida de imágenes múltiples y preview)
    - Modal para editar producto (con gestión de imágenes existentes vía AJAX)
    - Paginación que mantiene los filtros activos
--}}
@extends('layouts.admin')

@section('title', 'Productos')
@section('page-title', 'Productos')

@section('content')

{{-- ── Mensajes flash ── --}}
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

{{-- ── Header ── --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">
            Productos
            <span class="ml-2 text-base font-normal text-gray-400 bg-gray-100 px-2.5 py-0.5 rounded-full">
                {{ $productos->total() }}
            </span>
        </h2>
        <p class="text-gray-500 mt-1">Gestiona el inventario de tu tienda</p>
    </div>
    <button onclick="document.getElementById('modal-producto').classList.remove('hidden')"
            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-5 py-3 rounded-xl transition shadow-sm shadow-blue-600/20">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo producto
    </button>
</div>

{{-- ── Filtros ── --}}
@php
    $hayFiltros = request('categoria') || request('buscar') || request('stock_bajo');
@endphp

<form method="GET" action="{{ route('admin.productos.index') }}" id="form-filtros" class="mb-6">
    <div class="flex flex-wrap items-center gap-3">

        {{-- Select de categoría — auto-submit al cambiar --}}
        <select name="categoria"
                onchange="this.form.submit()"
                class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
            <option value="">Todas las categorías</option>
            @foreach ($categorias as $cat)
                <option value="{{ $cat->id }}" {{ request('categoria') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->nombre }}
                </option>
            @endforeach
        </select>

        {{-- Input de búsqueda por nombre o código --}}
        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2.5 focus-within:ring-2 focus-within:ring-blue-500 transition">
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="buscar"
                   value="{{ request('buscar') }}"
                   placeholder="Buscar por nombre o código..."
                   class="text-sm text-gray-700 outline-none bg-transparent w-56">
        </div>

        {{-- Botón de stock bajo — toggle activo/inactivo --}}
        @if (request('stock_bajo'))
            <a href="{{ request()->fullUrlWithoutQuery(['stock_bajo']) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-500 text-white text-sm font-medium rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Stock bajo
            </a>
        @else
            <button type="submit" name="stock_bajo" value="1"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-600 text-sm font-medium rounded-xl hover:border-amber-300 hover:text-amber-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Stock bajo
            </button>
        @endif

        {{-- Botón limpiar filtros — solo si hay filtros activos --}}
        @if ($hayFiltros)
            <a href="{{ route('admin.productos.index') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm text-gray-500 hover:text-gray-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Limpiar filtros
            </a>
        @endif
    </div>
</form>

{{-- ── Indicador de resultados ── --}}
@if ($hayFiltros)
    <p class="text-sm text-gray-500 mb-4">
        Mostrando <span class="font-medium text-gray-700">{{ $productos->total() }}</span> producto(s)
        @if (request('categoria'))
            en <span class="font-medium text-gray-700">{{ $categorias->find(request('categoria'))?->nombre }}</span>
        @endif
        @if (request('buscar'))
            con <span class="font-medium text-gray-700">"{{ request('buscar') }}"</span>
        @endif
        @if (request('stock_bajo'))
            <span class="text-amber-600 font-medium">con stock bajo</span>
        @endif
    </p>
@endif

{{-- ── Grid de productos ── --}}
@if ($productos->isNotEmpty())
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach ($productos as $producto)
            @php
                // Determinar color del semáforo de stock
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

            <div class="bg-white rounded-2xl border border-gray-100 hover:shadow-lg transition overflow-hidden group">

                {{-- Imagen del producto --}}
                <div class="relative aspect-square bg-gray-50">
                    @if ($producto->imagenPrincipal)
                        <img src="{{ asset('storage/' . $producto->imagenPrincipal->path) }}"
                             alt="{{ $producto->nombre }}"
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

                {{-- Información del producto --}}
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 text-sm leading-snug line-clamp-1">
                        {{ $producto->nombre }}
                    </h3>

                    @if ($producto->codigo)
                        <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $producto->codigo }}</p>
                    @endif

                    {{-- Precio de venta --}}
                    <p class="text-base font-bold text-gray-900 mt-2">
                        ${{ number_format($producto->precio_venta, 0, ',', '.') }}
                    </p>

                    {{-- Precio de compra tachado (referencia de costo) --}}
                    <p class="text-xs text-gray-400 line-through">
                        ${{ number_format($producto->precio_compra, 0, ',', '.') }}
                    </p>

                    {{-- Stock con semáforo de color --}}
                    <p class="text-xs font-medium mt-2 {{ $stockColor }}">
                        {{ $stockLabel }}
                    </p>

                    {{-- Botones de acción --}}
                    <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-50">
                        {{-- Botón editar --}}
                        <button onclick="abrirEditarProducto(
                                    {{ $producto->id }},
                                    {{ $producto->categoria_id }},
                                    '{{ addslashes($producto->codigo ?? '') }}',
                                    '{{ addslashes($producto->nombre) }}',
                                    '{{ addslashes($producto->descripcion ?? '') }}',
                                    {{ $producto->precio_compra }},
                                    {{ $producto->precio_venta }},
                                    {{ $producto->stock }},
                                    {{ $producto->stock_minimo }},
                                    {{ $producto->imagenes->map(fn($i) => ['id' => $i->id, 'path' => $i->path, 'es_principal' => $i->es_principal])->toJson() }}
                                )"
                                class="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-medium text-gray-600 bg-gray-50 hover:bg-blue-50 hover:text-blue-600 rounded-xl transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Editar
                        </button>

                        {{-- Botón eliminar --}}
                        <form method="POST"
                              action="{{ route('admin.productos.destroy', $producto) }}"
                              onsubmit="return confirm('¿Desactivar «{{ $producto->nombre }}»?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="p-2 text-gray-400 hover:bg-red-50 hover:text-red-600 rounded-xl transition"
                                    title="Desactivar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Paginación — conserva los filtros activos en los links --}}
    <div class="mt-8">
        {{ $productos->appends(request()->query())->links() }}
    </div>

@else
    {{-- Estado vacío --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-16 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
        </div>
        <p class="text-gray-500 font-medium">
            @if ($hayFiltros) No se encontraron productos con esos filtros
            @else No hay productos registrados @endif
        </p>
        @if ($hayFiltros)
            <a href="{{ route('admin.productos.index') }}"
               class="mt-4 inline-flex items-center gap-1.5 text-sm text-blue-600 hover:underline">
                Limpiar filtros
            </a>
        @else
            <button onclick="document.getElementById('modal-producto').classList.remove('hidden')"
                    class="mt-6 inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-5 py-2.5 rounded-xl transition">
                Crear primer producto
            </button>
        @endif
    </div>
@endif


{{-- ============================== --}}
{{-- MODAL — Crear producto          --}}
{{-- ============================== --}}
<div id="modal-producto" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50"
         onclick="document.getElementById('modal-producto').classList.add('hidden')"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

        <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 rounded-t-2xl flex items-center justify-between z-10">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Nuevo producto</h3>
                <p class="text-sm text-gray-500">Agrega un producto al inventario</p>
            </div>
            <button onclick="document.getElementById('modal-producto').classList.add('hidden')"
                    class="p-2 rounded-xl hover:bg-gray-100 transition">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST"
              action="{{ route('admin.productos.store') }}"
              enctype="multipart/form-data"
              class="p-6 space-y-5">
            @csrf

            {{-- Categoría y Código --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Categoría *</label>
                    <select name="categoria_id" required
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        <option value="">Selecciona...</option>
                        @foreach ($categorias as $cat)
                            <option value="{{ $cat->id }}" {{ old('categoria_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Código / SKU</label>
                    <input type="text" name="codigo" maxlength="50"
                           placeholder="Ej: SAM-S24-LCD"
                           value="{{ old('codigo') }}"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
            </div>

            {{-- Nombre --}}
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Nombre *</label>
                <input type="text" name="nombre" required maxlength="150"
                       placeholder="Ej: Pantalla Samsung Galaxy S24"
                       value="{{ old('nombre') }}"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>

            {{-- Descripción --}}
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Descripción</label>
                <textarea name="descripcion" rows="2"
                          placeholder="Especificaciones, compatibilidad, notas..."
                          class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition resize-none">{{ old('descripcion') }}</textarea>
            </div>

            {{-- Precios --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Precio compra *</label>
                    <input type="text" id="precio_compra_visual" placeholder="50.000" inputmode="numeric"
                           oninput="formatearPrecio(this, 'precio_compra')"
                           value="{{ old('precio_compra') ? number_format(old('precio_compra'), 0, ',', '.') : '' }}"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    <input type="hidden" name="precio_compra" id="precio_compra" value="{{ old('precio_compra') }}">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Precio venta *</label>
                    <input type="text" id="precio_venta_visual" placeholder="80.000" inputmode="numeric"
                           oninput="formatearPrecio(this, 'precio_venta')"
                           value="{{ old('precio_venta') ? number_format(old('precio_venta'), 0, ',', '.') : '' }}"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    <input type="hidden" name="precio_venta" id="precio_venta" value="{{ old('precio_venta') }}">
                </div>
            </div>

            {{-- Stock --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Stock actual *</label>
                    <input type="number" name="stock" min="0" required
                           value="{{ old('stock', 0) }}"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Stock mínimo</label>
                    <input type="number" name="stock_minimo" min="0"
                           value="{{ old('stock_minimo', 3) }}"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
            </div>

            {{-- Imágenes --}}
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Imágenes (máx. 2 MB c/u)</label>
                <input type="file" name="imagenes[]" id="input-imagenes-crear"
                       multiple accept="image/*"
                       onchange="mostrarPreviewImagenes(this, 'preview-imagenes-crear')"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100">

                {{-- Preview de imágenes seleccionadas --}}
                <div id="preview-imagenes-crear" class="mt-3 flex flex-wrap gap-2"></div>
            </div>

            {{-- Errores de validación --}}
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button"
                        onclick="document.getElementById('modal-producto').classList.add('hidden')"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-sm shadow-blue-600/20">
                    Guardar producto
                </button>
            </div>
        </form>
    </div>
</div>


{{-- ============================== --}}
{{-- MODAL — Editar producto         --}}
{{-- ============================== --}}
<div id="modal-editar-producto" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50"
         onclick="document.getElementById('modal-editar-producto').classList.add('hidden')"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

        <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 rounded-t-2xl flex items-center justify-between z-10">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Editar producto</h3>
                <p class="text-sm text-gray-500" id="editar-producto-subtitulo"></p>
            </div>
            <button onclick="document.getElementById('modal-editar-producto').classList.add('hidden')"
                    class="p-2 rounded-xl hover:bg-gray-100 transition">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="form-editar-producto"
              method="POST"
              action=""
              enctype="multipart/form-data"
              class="p-6 space-y-5">
            @csrf
            @method('PATCH')

            {{-- Categoría y Código --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Categoría *</label>
                    <select name="categoria_id" id="editar-categoria_id" required
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        @foreach ($categorias as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Código / SKU</label>
                    <input type="text" name="codigo" id="editar-codigo" maxlength="50"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
            </div>

            {{-- Nombre --}}
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Nombre *</label>
                <input type="text" name="nombre" id="editar-nombre" required maxlength="150"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>

            {{-- Descripción --}}
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Descripción</label>
                <textarea name="descripcion" id="editar-descripcion" rows="2"
                          class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition resize-none"></textarea>
            </div>

            {{-- Precios --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Precio compra *</label>
                    <input type="text" id="editar-precio_compra_visual" inputmode="numeric"
                           oninput="formatearPrecio(this, 'editar-precio_compra')"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    <input type="hidden" name="precio_compra" id="editar-precio_compra">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Precio venta *</label>
                    <input type="text" id="editar-precio_venta_visual" inputmode="numeric"
                           oninput="formatearPrecio(this, 'editar-precio_venta')"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    <input type="hidden" name="precio_venta" id="editar-precio_venta">
                </div>
            </div>

            {{-- Stock --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Stock actual *</label>
                    <input type="number" name="stock" id="editar-stock" min="0" required
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Stock mínimo</label>
                    <input type="number" name="stock_minimo" id="editar-stock_minimo" min="0"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
            </div>

            {{-- Imágenes existentes --}}
            <div>
                <label class="text-xs text-gray-500 mb-2 block">Imágenes actuales</label>
                <div id="imagenes-existentes" class="flex flex-wrap gap-2 mb-3"></div>
            </div>

            {{-- Agregar imágenes nuevas --}}
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Agregar imágenes nuevas</label>
                <input type="file" name="imagenes[]" id="input-imagenes-editar"
                       multiple accept="image/*"
                       onchange="mostrarPreviewImagenes(this, 'preview-imagenes-editar')"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100">
                <div id="preview-imagenes-editar" class="mt-3 flex flex-wrap gap-2"></div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button"
                        onclick="document.getElementById('modal-editar-producto').classList.add('hidden')"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                    Cancelar
                </button>
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // ══════════════════════════════════════
    // FORMATEO DE PRECIOS (estilo colombiano)
    // ══════════════════════════════════════

    /**
     * Formatea un input de precio con puntos de miles al estilo colombiano.
     * El usuario ve "150.000"; el campo oculto guarda "150000" para el servidor.
     *
     * @param {HTMLInputElement} input    Input visible donde escribe el usuario
     * @param {string}           hiddenId ID del input hidden que va al servidor
     */
    function formatearPrecio(input, hiddenId) {
        // Quitar todo lo que no sea dígito
        const valor = input.value.replace(/\D/g, '');

        // El campo hidden recibe el número limpio (lo que valida Laravel)
        document.getElementById(hiddenId).value = valor;

        // El campo visible muestra el número formateado con puntos de miles
        if (valor) {
            input.value = parseInt(valor).toLocaleString('es-CO');
        }
    }

    // ══════════════════════════════════════
    // PREVIEW DE IMÁGENES AL SELECCIONAR
    // ══════════════════════════════════════

    /**
     * Genera thumbnails de las imágenes seleccionadas en el input file.
     * Cada thumbnail tiene un botón X para quitarlo del FileList.
     *
     * @param {HTMLInputElement} input     El input[type=file]
     * @param {string}           previewId ID del contenedor de thumbnails
     */
    function mostrarPreviewImagenes(input, previewId) {
        const container = document.getElementById(previewId);
        container.innerHTML = ''; // Limpiar previews anteriores

        // FileList no es un array real, se convierte con Array.from
        Array.from(input.files).forEach((file, index) => {
            const reader = new FileReader();

            reader.onload = (e) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'relative w-20 h-20 rounded-xl overflow-hidden border border-gray-200 group';
                wrapper.dataset.index = index;

                wrapper.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover">
                    <button type="button"
                            onclick="quitarPreview(this, '${input.id}', ${index})"
                            class="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition font-bold leading-none">
                        ×
                    </button>`;

                container.appendChild(wrapper);
            };

            reader.readAsDataURL(file);
        });
    }

    /**
     * Quita una imagen del preview y del FileList.
     * Como FileList es inmutable, se crea un nuevo DataTransfer con los archivos restantes.
     *
     * @param {HTMLButtonElement} btn     Botón X que se presionó
     * @param {string}            inputId ID del input[type=file]
     * @param {number}            index   Índice del archivo a quitar
     */
    function quitarPreview(btn, inputId, index) {
        const input   = document.getElementById(inputId);
        const wrapper = btn.closest('[data-index]');

        // Construir nuevo FileList sin el archivo eliminado
        const dt = new DataTransfer();
        Array.from(input.files).forEach((file, i) => {
            if (i !== index) dt.items.add(file);
        });
        input.files = dt.files;

        // Quitar el thumbnail del DOM
        wrapper.remove();
    }

    // ══════════════════════════════════════
    // MODAL DE EDICIÓN DE PRODUCTO
    // ══════════════════════════════════════

    /**
     * Abre el modal de edición y lo pre-llena con los datos del producto.
     * También renderiza las imágenes existentes con botón de eliminación AJAX.
     *
     * @param {number} id           ID del producto
     * @param {number} categoriaId  ID de la categoría actual
     * @param {string} codigo       Código/SKU actual
     * @param {string} nombre       Nombre actual
     * @param {string} descripcion  Descripción actual
     * @param {number} precioCompra Precio de compra (número puro)
     * @param {number} precioVenta  Precio de venta (número puro)
     * @param {number} stock        Stock actual
     * @param {number} stockMinimo  Stock mínimo actual
     * @param {Array}  imagenes     Array de objetos {id, path, es_principal}
     */
    function abrirEditarProducto(id, categoriaId, codigo, nombre, descripcion,
                                  precioCompra, precioVenta, stock, stockMinimo, imagenes) {
        // Actualizar action del form con el ID correcto
        const baseUrl = '{{ url("admin/productos") }}';
        document.getElementById('form-editar-producto').action = `${baseUrl}/${id}`;

        // Subtítulo del modal
        document.getElementById('editar-producto-subtitulo').textContent = nombre;

        // Pre-llenar campos de texto
        document.getElementById('editar-codigo').value       = codigo;
        document.getElementById('editar-nombre').value       = nombre;
        document.getElementById('editar-descripcion').value  = descripcion;
        document.getElementById('editar-stock').value        = stock;
        document.getElementById('editar-stock_minimo').value = stockMinimo;

        // Seleccionar la categoría correcta en el select
        document.getElementById('editar-categoria_id').value = categoriaId;

        // Campos de precio: el visual muestra con puntos, el hidden guarda número puro
        document.getElementById('editar-precio_compra').value        = precioCompra;
        document.getElementById('editar-precio_compra_visual').value = parseInt(precioCompra).toLocaleString('es-CO');
        document.getElementById('editar-precio_venta').value         = precioVenta;
        document.getElementById('editar-precio_venta_visual').value  = parseInt(precioVenta).toLocaleString('es-CO');

        // Limpiar previews de imágenes nuevas de una edición anterior
        document.getElementById('preview-imagenes-editar').innerHTML = '';
        document.getElementById('input-imagenes-editar').value       = '';

        // Renderizar imágenes existentes con botón de eliminar
        const contenedor = document.getElementById('imagenes-existentes');
        contenedor.innerHTML = '';

        if (imagenes && imagenes.length > 0) {
            imagenes.forEach(img => {
                const wrapper = document.createElement('div');
                wrapper.id        = `img-wrapper-${img.id}`;
                wrapper.className = 'relative w-20 h-20 rounded-xl overflow-hidden border-2 group ' +
                                    (img.es_principal ? 'border-blue-400' : 'border-gray-200');

                wrapper.innerHTML = `
                    <img src="/storage/${img.path}" class="w-full h-full object-cover">
                    ${img.es_principal ? '<span class="absolute bottom-0 left-0 right-0 bg-blue-500/80 text-white text-center text-[10px] py-0.5">Principal</span>' : ''}
                    <button type="button"
                            onclick="eliminarImagenExistente(${img.id}, this)"
                            class="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition font-bold leading-none">
                        ×
                    </button>`;

                contenedor.appendChild(wrapper);
            });
        } else {
            contenedor.innerHTML = '<p class="text-xs text-gray-400 italic">Sin imágenes aún</p>';
        }

        // Mostrar el modal
        document.getElementById('modal-editar-producto').classList.remove('hidden');
    }

    // ══════════════════════════════════════
    // ELIMINAR IMAGEN EXISTENTE (AJAX)
    // ══════════════════════════════════════

    /**
     * Elimina una imagen existente del servidor vía AJAX (DELETE).
     * Si el servidor confirma el éxito, quita el thumbnail del DOM.
     *
     * @param {number}          imagenId ID de la ProductoImagen a eliminar
     * @param {HTMLButtonElement} btn    Botón × que disparó la acción
     */
    function eliminarImagenExistente(imagenId, btn) {
        if (!confirm('¿Eliminar esta imagen permanentemente?')) return;

        const wrapper = document.getElementById(`img-wrapper-${imagenId}`);
        const baseUrl = '{{ url("admin/producto-imagenes") }}';

        // Indicador visual de carga
        btn.textContent = '…';
        btn.disabled    = true;

        fetch(`${baseUrl}/${imagenId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept':       'application/json',
            },
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Animar salida y quitar del DOM
                wrapper.style.transition = 'opacity 0.3s, transform 0.3s';
                wrapper.style.opacity    = '0';
                wrapper.style.transform  = 'scale(0.8)';
                setTimeout(() => wrapper.remove(), 300);

                mostrarNotificacion('Imagen eliminada');
            } else {
                btn.textContent = '×';
                btn.disabled    = false;
                mostrarNotificacion(data.message || 'Error al eliminar', 'error');
            }
        })
        .catch(() => {
            btn.textContent = '×';
            btn.disabled    = false;
            mostrarNotificacion('Error de conexión', 'error');
        });
    }

    // ══════════════════════════════════════
    // NOTIFICACIÓN FLOTANTE
    // ══════════════════════════════════════

    /**
     * Muestra una notificación toast que desaparece sola después de 3 segundos.
     *
     * @param {string} mensaje Texto a mostrar
     * @param {string} tipo    'success' (verde) o 'error' (rojo)
     */
    function mostrarNotificacion(mensaje, tipo = 'success') {
        const noti = document.createElement('div');
        noti.className = `fixed top-6 right-6 z-50 flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-sm font-medium transition-all duration-300 transform translate-x-full ${
            tipo === 'success'
                ? 'bg-green-50 border border-green-200 text-green-700'
                : 'bg-red-50 border border-red-200 text-red-700'
        }`;

        noti.innerHTML = tipo === 'success'
            ? `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
               </svg>${mensaje}`
            : `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
               </svg>${mensaje}`;

        document.body.appendChild(noti);

        // Animar entrada desde la derecha
        requestAnimationFrame(() => noti.classList.remove('translate-x-full'));

        // Desaparecer después de 3 segundos
        setTimeout(() => {
            noti.classList.add('translate-x-full');
            setTimeout(() => noti.remove(), 300);
        }, 3000);
    }

    // Auto-ocultar alertas flash después de 4 segundos
    ['alert-success', 'alert-error'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            setTimeout(() => {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity    = '0';
                setTimeout(() => el.remove(), 500);
            }, 4000);
        }
    });

    // Si hay errores de validación, re-abrir el modal de creación automáticamente
    @if ($errors->any())
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('modal-producto').classList.remove('hidden');
        });
    @endif
</script>
@endsection
