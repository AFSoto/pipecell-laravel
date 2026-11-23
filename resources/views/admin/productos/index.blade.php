{{--
    Vista de gestión de productos del inventario.

    Incluye:
    - Filtros por categoría, búsqueda de texto y stock bajo
    - Productos en tarjetas (12 por página) o en lista con miniatura (100 por página).
      Cada equipo elige la suya: ver ProductoController::vistaElegida(). Se dibuja solo
      la vista elegida (partials/tarjeta o partials/fila), con las piezas compartidas
      partials/stock, partials/etiquetas y partials/acciones
    - Modal para crear producto (con subida de imágenes múltiples y preview)
    - Modal para editar producto (con gestión de imágenes existentes vía AJAX)
    - Paginación que mantiene los filtros activos
--}}
@extends('layouts.admin')

@section('title', 'Productos')
@section('page-title', 'Productos')

@section('content')

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
    {{-- Botón submit oculto sin nombre — captura el Enter del campo buscar sin agregar stock_bajo=1 --}}
    <button type="submit" class="hidden"></button>
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

        {{-- Input de búsqueda por nombre, marca o código --}}
        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2.5 focus-within:ring-2 focus-within:ring-blue-500 transition">
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="buscar"
                   value="{{ request('buscar') }}"
                   placeholder="Nombre, marca o código..."
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

        {{-- Vista: tarjetas o lista. Son enlaces: conservan los filtros y vuelven a la
             página 1 (cambia cuántos van por página). El controlador la recuerda. --}}
        <div class="ml-auto inline-flex items-center gap-1 p-1 bg-white border border-gray-200 rounded-xl"
             role="group" aria-label="Vista">
            @foreach ([
                'tarjetas' => ['Ver como tarjetas', 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z'],
                'lista'    => ['Ver como lista', 'M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z'],
            ] as $opcion => [$titulo, $icono])
                <a href="{{ request()->fullUrlWithQuery(['vista' => $opcion, 'page' => null]) }}"
                   title="{{ $titulo }}" aria-label="{{ $titulo }}"
                   @if ($vista === $opcion) aria-current="true" @endif
                   class="p-2 pointer-coarse:p-3 rounded-lg transition {{ $vista === $opcion ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-400 hover:text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icono }}"/>
                    </svg>
                </a>
            @endforeach
        </div>
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

{{-- ── Productos: se dibuja solo la vista elegida (tarjetas o lista) ── --}}
@if ($productos->isNotEmpty())
    @if ($vista === 'lista')
        <div data-vista="lista" class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="py-3 pl-4 pr-2"><span class="sr-only">Foto</span></th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Producto</th>
                            <th class="hidden lg:table-cell text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Categoría</th>
                            <th class="text-right py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Precio</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Stock</th>
                            <th class="text-right py-3 pl-4 pr-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($productos as $producto)
                            @include('admin.productos.partials.fila', ['producto' => $producto])
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div data-vista="tarjetas" class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach ($productos as $producto)
                @include('admin.productos.partials.tarjeta', ['producto' => $producto])
            @endforeach
        </div>
    @endif

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
@php
    // Lo que se había escrito en "Nuevo producto", solo si el que falló fue este
    // formulario (errores en la bolsa por defecto). Si falló editar, old() trae los
    // datos de la edición y no deben aparecer aquí (ver UpdateProductoRequest::$errorBag).
    $viejo = fn (string $campo, $defecto = null) => $errors->any() ? old($campo, $defecto) : $defecto;
@endphp
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

            {{-- Errores de validación --}}
            @if ($errors->any())
                <div id="crear-errores" class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 transition-opacity duration-500">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-red-700 mb-1">Corrige los siguientes errores:</p>
                            <ul class="list-disc list-inside space-y-0.5 text-sm text-red-600">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <p class="text-xs text-red-500 mt-2">Si habías elegido fotos, vuelve a elegirlas: el navegador no las conserva.</p>
                        </div>
                        <button type="button" onclick="ocultarErroresCrear()"
                                class="flex-shrink-0 p-1 rounded-lg hover:bg-red-100 transition text-red-400 hover:text-red-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Categoría y Tipo --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Categoría *</label>
                    <select name="categoria_id" id="crear-categoria_id" required
                            onchange="toggleMarco(this.value, 'crear')"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        <option value="">Selecciona...</option>
                        @foreach ($categorias as $cat)
                            <option value="{{ $cat->id }}" {{ $viejo('categoria_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Tipo de producto</label>
                    <select name="tipo_producto_id"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        <option value="">Sin tipo</option>
                        @foreach ($tiposProducto as $tipo)
                            <option value="{{ $tipo->id }}" {{ $viejo('tipo_producto_id') == $tipo->id ? 'selected' : '' }}>
                                {{ $tipo->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Campo marco (solo visible para categoría Pantallas) --}}
            <div id="crear-seccion-marco" class="hidden bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                <label class="text-xs font-medium text-amber-800 mb-2 block">¿La pantalla viene con marco? *</label>
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="marco" id="crear-marco-si" value="1"
                               {{ $viejo('marco') === '1' ? 'checked' : '' }}
                               onchange="generarNombrePantalla('crear')"
                               class="w-4 h-4 text-blue-600 accent-blue-600">
                        <span class="text-sm text-gray-700">Sí, viene con marco</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="marco" id="crear-marco-no" value="0"
                               {{ $viejo('marco') === '0' ? 'checked' : '' }}
                               onchange="generarNombrePantalla('crear')"
                               class="w-4 h-4 text-blue-600 accent-blue-600">
                        <span class="text-sm text-gray-700">No, sin marco</span>
                    </label>
                </div>
            </div>

            {{-- Marca (todas las categorías) y Referencia (solo pantallas).
                 En pantallas la marca es la del celular, es obligatoria y arma el nombre. En el
                 resto es la del producto y permite tener varios con el mismo nombre (dos
                 "Batería iPhone 14" de distinta marca). mostrarCamposSegunCategoria() pone el
                 asterisco y el ejemplo, y muestra u oculta la referencia. --}}
            <div id="crear-seccion-marca" class="grid grid-cols-2 gap-4">
                <div>
                    <label for="crear-marca" class="text-xs text-gray-500 mb-1 block">Marca<span id="crear-marca-obligatoria" class="hidden"> *</span></label>
                    <input type="text" name="marca" id="crear-marca" maxlength="100"
                           placeholder="Ej: Deji, Anker"
                           value="{{ $viejo('marca') }}"
                           oninput="generarNombrePantalla('crear')"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div id="crear-wrapper-referencia" class="hidden">
                    <label class="text-xs text-gray-500 mb-1 block">Referencia *</label>
                    <input type="text" name="referencia" id="crear-referencia" maxlength="100"
                           placeholder="Ej: G20, 15C"
                           value="{{ $viejo('referencia') }}"
                           oninput="generarNombrePantalla('crear')"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
            </div>

            {{-- Preview nombre auto-generado (solo pantallas) --}}
            <div id="crear-preview-nombre" class="hidden bg-blue-50 border border-blue-200 rounded-xl px-4 py-3">
                <p class="text-xs text-blue-600 mb-0.5">Nombre generado automáticamente</p>
                <p id="crear-nombre-preview-text" class="text-sm font-semibold text-blue-900">—</p>
            </div>

            {{-- Código y Nombre --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Código / SKU</label>
                    <input type="text" name="codigo" maxlength="50"
                           placeholder="Ej: SAM-S24-LCD"
                           value="{{ $viejo('codigo') }}"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div id="crear-wrapper-nombre">
                    <label class="text-xs text-gray-500 mb-1 block">Nombre *</label>
                    <input type="text" name="nombre" id="crear-nombre" required maxlength="150"
                           placeholder="Ej: Pantalla Samsung Galaxy S24"
                           value="{{ $viejo('nombre') }}"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
            </div>

            {{-- Descripción --}}
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Descripción</label>
                <textarea name="descripcion" rows="2"
                          placeholder="Especificaciones, compatibilidad, notas..."
                          class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition resize-none">{{ $viejo('descripcion') }}</textarea>
            </div>

            {{-- Precios. El de compra es opcional: vacío = sin precio de compra (no es lo mismo que 0).
                 is_numeric(): un 0 escrito vuelve como 0 (no vacío) y un valor raro no rompe la página. --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Precio compra</label>
                    <input type="text" id="precio_compra_visual" placeholder="50.000" inputmode="numeric"
                           oninput="formatearPrecio(this, 'precio_compra')"
                           value="{{ is_numeric($viejo('precio_compra')) ? number_format($viejo('precio_compra'), 0, ',', '.') : '' }}"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    <input type="hidden" name="precio_compra" id="precio_compra" value="{{ $viejo('precio_compra') }}">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Precio venta *</label>
                    <input type="text" id="precio_venta_visual" placeholder="80.000" inputmode="numeric"
                           oninput="formatearPrecio(this, 'precio_venta')"
                           value="{{ is_numeric($viejo('precio_venta')) ? number_format($viejo('precio_venta'), 0, ',', '.') : '' }}"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    <input type="hidden" name="precio_venta" id="precio_venta" value="{{ $viejo('precio_venta') }}">
                </div>
            </div>

            {{-- Stock --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Stock actual *</label>
                    <input type="number" name="stock" min="0" required
                           value="{{ $viejo('stock', 0) }}"
                           onfocus="if(this.value==='0') this.value=''"
                           onblur="if(this.value==='') this.value='0'"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Stock mínimo</label>
                    <input type="number" name="stock_minimo" min="0"
                           value="{{ $viejo('stock_minimo', 3) }}"
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
            {{-- La query de esta página: al guardar se vuelve a la misma lista --}}
            <input type="hidden" name="volver" value="{{ request()->getQueryString() }}">

            {{-- Errores de validación de editar (bolsa propia: ver UpdateProductoRequest) --}}
            @if ($errors->editarProducto->any())
                <div id="editar-errores" class="bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-red-700 mb-1">Corrige los siguientes errores:</p>
                            <ul class="list-disc list-inside space-y-0.5 text-sm text-red-600">
                                @foreach ($errors->editarProducto->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <p class="text-xs text-red-500 mt-2">Si habías elegido fotos nuevas, vuelve a elegirlas: el navegador no las conserva.</p>
                        </div>
                        <button type="button" onclick="document.getElementById('editar-errores').remove()"
                                aria-label="Cerrar errores"
                                class="flex-shrink-0 p-1 rounded-lg hover:bg-red-100 transition text-red-400 hover:text-red-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Categoría y Tipo --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Categoría *</label>
                    <select name="categoria_id" id="editar-categoria_id" required
                            onchange="toggleMarco(this.value, 'editar')"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        @foreach ($categorias as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Tipo de producto</label>
                    <select name="tipo_producto_id" id="editar-tipo_producto_id"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        <option value="">Sin tipo</option>
                        @foreach ($tiposProducto as $tipo)
                            <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Campo marco (solo visible para categoría Pantallas) --}}
            <div id="editar-seccion-marco" class="hidden bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                <label class="text-xs font-medium text-amber-800 mb-2 block">¿La pantalla viene con marco? *</label>
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="marco" id="editar-marco-si" value="1"
                               onchange="generarNombrePantalla('editar')"
                               class="w-4 h-4 text-blue-600 accent-blue-600">
                        <span class="text-sm text-gray-700">Sí, viene con marco</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="marco" id="editar-marco-no" value="0"
                               onchange="generarNombrePantalla('editar')"
                               class="w-4 h-4 text-blue-600 accent-blue-600">
                        <span class="text-sm text-gray-700">No, sin marco</span>
                    </label>
                </div>
            </div>

            {{-- Marca (todas las categorías) y Referencia (solo pantallas): ver el modal de crear --}}
            <div id="editar-seccion-marca" class="grid grid-cols-2 gap-4">
                <div>
                    <label for="editar-marca" class="text-xs text-gray-500 mb-1 block">Marca<span id="editar-marca-obligatoria" class="hidden"> *</span></label>
                    <input type="text" name="marca" id="editar-marca" maxlength="100"
                           placeholder="Ej: Deji, Anker"
                           oninput="generarNombrePantalla('editar')"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div id="editar-wrapper-referencia" class="hidden">
                    <label class="text-xs text-gray-500 mb-1 block">Referencia *</label>
                    <input type="text" name="referencia" id="editar-referencia" maxlength="100"
                           placeholder="Ej: G20, 15C"
                           oninput="generarNombrePantalla('editar')"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
            </div>

            {{-- Preview nombre auto-generado (solo pantallas) --}}
            <div id="editar-preview-nombre" class="hidden bg-blue-50 border border-blue-200 rounded-xl px-4 py-3">
                <p class="text-xs text-blue-600 mb-0.5">Nombre generado automáticamente</p>
                <p id="editar-nombre-preview-text" class="text-sm font-semibold text-blue-900">—</p>
            </div>

            {{-- Código y Nombre --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Código / SKU</label>
                    <input type="text" name="codigo" id="editar-codigo" maxlength="50"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div id="editar-wrapper-nombre">
                    <label class="text-xs text-gray-500 mb-1 block">Nombre *</label>
                    <input type="text" name="nombre" id="editar-nombre" required maxlength="150"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
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
                    <label class="text-xs text-gray-500 mb-1 block">Precio compra</label>
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

{{-- ============================== --}}
{{-- MODAL — Ajuste rápido de stock  --}}
{{-- ============================== --}}
<div id="modal-ajuste-stock" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="cerrarAjusteStock()"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm">

        {{-- Header --}}
        <div class="px-6 pt-5 pb-4 border-b border-gray-100 flex items-start justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold text-gray-900">Ajustar stock</h3>
                <p id="ajuste-nombre" class="text-sm text-gray-500 mt-0.5 line-clamp-1"></p>
            </div>
            <button onclick="cerrarAjusteStock()" class="p-1.5 rounded-xl hover:bg-gray-100 transition flex-shrink-0">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="px-6 py-5 space-y-5">

            {{-- Stock actual --}}
            <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Stock actual</span>
                <span id="ajuste-stock-actual-badge" class="text-xl font-bold text-gray-900"></span>
            </div>

            {{-- Selector de operación --}}
            <div class="bg-gray-100 p-1 rounded-xl flex gap-1">
                <button id="ajuste-tab-entrada"
                        onclick="setAjusteOperacion('entrada')"
                        class="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-medium rounded-lg transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Entrada
                </button>
                <button id="ajuste-tab-salida"
                        onclick="setAjusteOperacion('salida')"
                        class="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-medium rounded-lg transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/>
                    </svg>
                    Salida
                </button>
            </div>

            {{-- Input de cantidad --}}
            <div>
                <label id="ajuste-label" class="text-xs text-gray-500 mb-1.5 block"></label>
                <input type="number" id="ajuste-cantidad" min="0"
                       oninput="actualizarPreviewStock()"
                       placeholder="0"
                       class="w-full px-4 py-3 text-center text-2xl font-bold border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-400 transition">
            </div>

            {{-- Preview del resultado --}}
            <div id="ajuste-preview-wrap" class="hidden bg-gray-50 rounded-xl px-4 py-3 flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Resultado</span>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-400 line-through" id="ajuste-preview-anterior"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span id="ajuste-preview-nuevo" class="text-lg font-bold"></span>
                </div>
            </div>

            {{-- Alerta de salida mayor que stock --}}
            <div id="ajuste-alerta-salida" class="hidden bg-amber-50 border border-amber-200 text-amber-700 text-xs rounded-xl px-4 py-2.5 flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                La cantidad supera el stock disponible. No se puede guardar.
            </div>

            {{-- Motivo del ajuste --}}
            <div>
                <label class="text-xs text-gray-500 mb-1.5 block">Motivo <span class="text-red-500">*</span></label>
                <select id="ajuste-tipo_movimiento_id"
                        onchange="toggleNuevoTipo(this.value)"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    <option value="">Selecciona un motivo...</option>
                    @foreach ($tiposMovimiento as $tipo)
                        <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                    @endforeach
                    <option value="nuevo">— Otro (crear nuevo) —</option>
                </select>

                <input type="text" id="ajuste-nuevo_tipo"
                       placeholder="Nombre del nuevo motivo..."
                       maxlength="100"
                       class="hidden mt-2 w-full px-3 py-2.5 border border-blue-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition bg-blue-50">
            </div>

            {{-- Nota opcional --}}
            <div>
                <label class="text-xs text-gray-500 mb-1.5 block">Nota <span class="text-gray-400">(opcional)</span></label>
                <input type="text" id="ajuste-nota"
                       placeholder="Ej: Recepción de mercancía proveedor X"
                       maxlength="500"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 pb-5 flex items-center gap-3">
            <button onclick="cerrarAjusteStock()"
                    class="flex-1 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                Cancelar
            </button>
            <button id="ajuste-btn-guardar"
                    onclick="guardarAjusteStock()"
                    class="flex-1 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed">
                Guardar
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // IDs de las categorías de pantallas (Categoria::esDePantallas), para mostrar el campo marco
    const categoriasConMarco = @json($categorias->filter->esDePantallas()->pluck('id')->values());

    // Ejemplo del campo Marca: en pantallas es la marca del celular; en el resto, la del producto
    const ejemploMarca = { pantalla: 'Ej: Xiaomi, Motorola', otra: 'Ej: Deji, Anker' };

    function esCategoriaDePantallas(categoriaId) {
        return categoriasConMarco.includes(parseInt(categoriaId));
    }

    /**
     * Muestra u oculta lo que depende de la categoría, sin borrar nada: marco, referencia
     * y la vista previa del nombre (solo pantallas), y el asterisco y el ejemplo de Marca.
     * Al volver con errores, el modal de crear usa solo esta: lo escrito se queda.
     * @param {string|number} categoriaId  ID de la categoría seleccionada
     * @param {string}        prefijo      'crear' o 'editar'
     * @returns {boolean} si la categoría es de pantallas
     */
    function mostrarCamposSegunCategoria(categoriaId, prefijo) {
        const esPantalla = esCategoriaDePantallas(categoriaId);

        document.getElementById(`${prefijo}-seccion-marco`).classList.toggle('hidden', !esPantalla);
        document.getElementById(`${prefijo}-wrapper-referencia`).classList.toggle('hidden', !esPantalla);
        document.getElementById(`${prefijo}-marca-obligatoria`).classList.toggle('hidden', !esPantalla);
        document.getElementById(`${prefijo}-marca`).placeholder = esPantalla ? ejemploMarca.pantalla : ejemploMarca.otra;

        // Nombre: en pantallas se arma solo (vista previa); en el resto se escribe
        document.getElementById(`${prefijo}-wrapper-nombre`).classList.toggle('hidden', esPantalla);
        document.getElementById(`${prefijo}-preview-nombre`).classList.toggle('hidden', !esPantalla);

        // Para que toggleMarco() sepa de qué clase de categoría se viene
        document.getElementById(`${prefijo}-seccion-marca`).dataset.pantalla = esPantalla ? '1' : '0';

        return esPantalla;
    }

    /**
     * Al cambiar la categoría (y al abrir editar): muestra los campos que tocan y limpia lo
     * que quedó oculto, para no enviar datos que no se ven.
     * Al pasar entre pantallas y otra categoría (o al revés) también se borra la marca,
     * porque cambia lo que significa (la del celular o la del producto), y, si se venía de
     * pantallas, el nombre generado. Entre categorías que no son de pantallas se conservan.
     * @param {string|number} categoriaId  ID de la categoría seleccionada
     * @param {string}        prefijo      'crear' o 'editar'
     */
    function toggleMarco(categoriaId, prefijo) {
        const antes      = document.getElementById(`${prefijo}-seccion-marca`).dataset.pantalla; // '1', '0' o sin categoría aún
        const esPantalla = mostrarCamposSegunCategoria(categoriaId, prefijo);

        if (antes !== undefined && antes !== (esPantalla ? '1' : '0')) {
            document.getElementById(`${prefijo}-marca`).value = '';
        }

        if (esPantalla) {
            generarNombrePantalla(prefijo);
            return;
        }

        document.getElementById(`${prefijo}-seccion-marco`).querySelectorAll('input[type=radio]').forEach(r => r.checked = false);
        document.getElementById(`${prefijo}-referencia`).value = '';

        // Limpiar el valor auto-generado para no confundir al servidor
        if (antes === '1') {
            document.getElementById(`${prefijo}-nombre`).value = '';
        }
    }

    /**
     * Arma el nombre de una pantalla: "Pantalla {marca} {referencia}".
     * Solo en categorías de pantallas: Marca se ve en todas y llama a esta función al
     * escribir, y en una batería no debe pisar el nombre que se escribió.
     * @param {string} prefijo 'crear' o 'editar'
     */
    function generarNombrePantalla(prefijo) {
        if (!esCategoriaDePantallas(document.getElementById(`${prefijo}-categoria_id`).value)) {
            return;
        }

        const partes = ['Pantalla'];

        const marca = document.getElementById(`${prefijo}-marca`)?.value.trim();
        if (marca) partes.push(marca);

        const ref = document.getElementById(`${prefijo}-referencia`)?.value.trim();
        if (ref) partes.push(ref);

        const nombre = partes.join(' ');

        // Actualizar el input oculto (se envía con el formulario)
        document.getElementById(`${prefijo}-nombre`).value = nombre;
        // Actualizar el preview visual
        document.getElementById(`${prefijo}-nombre-preview-text`).textContent = nombre;
    }

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
     * @param {?string} precioCompra Precio de compra ('20000.00'), o null si el producto no tiene
     * @param {number} precioVenta  Precio de venta (número puro)
     * @param {number} stock        Stock actual
     * @param {number} stockMinimo  Stock mínimo actual
     * @param {Array}  imagenes     Array de objetos {id, path, es_principal}
     * @param {boolean} conErrores  true solo al reabrir después de un error de validación:
     *                              deja visible el bloque de errores. Al abrir otro producto
     *                              se borra, para no mostrar errores que no son suyos.
     */
    function abrirEditarProducto(id, categoriaId, tipoProductoId, codigo, nombre, descripcion,
                                  precioCompra, precioVenta, stock, stockMinimo, marco, marca, referencia, imagenes,
                                  conErrores = false) {
        const baseUrl = '{{ url("admin/productos") }}';
        document.getElementById('form-editar-producto').action = `${baseUrl}/${id}`;

        if (!conErrores) {
            document.getElementById('editar-errores')?.remove();
        }

        document.getElementById('editar-producto-subtitulo').textContent = nombre;

        document.getElementById('editar-codigo').value       = codigo;
        document.getElementById('editar-descripcion').value  = descripcion;
        document.getElementById('editar-stock').value        = stock;
        document.getElementById('editar-stock_minimo').value = stockMinimo;

        document.getElementById('editar-categoria_id').value      = categoriaId;
        document.getElementById('editar-tipo_producto_id').value  = tipoProductoId ?? '';

        // Mostrar/ocultar marco y referencia según la categoría. Marca y referencia se ponen
        // después: toggleMarco() las borra si el producto anterior era de otra clase de categoría
        toggleMarco(categoriaId, 'editar');
        if (marco !== null) {
            const radioId = marco == 1 ? 'editar-marco-si' : 'editar-marco-no';
            document.getElementById(radioId).checked = true;
        }
        document.getElementById('editar-marca').value      = marca ?? '';
        document.getElementById('editar-referencia').value = referencia ?? '';

        // Nombre: va después de toggleMarco, que lo borra al pasar de pantallas a otra
        // categoría (era el generado). Las pantallas lo arman con marca y referencia; el
        // resto conserva el suyo. Antes se regeneraba siempre, y un accesorio abría con el
        // nombre "Pantalla": al guardar quedaba renombrado.
        if (categoriasConMarco.includes(parseInt(categoriaId))) {
            generarNombrePantalla('editar');
        } else {
            document.getElementById('editar-nombre').value = nombre;
        }

        // Campos de precio: el visual muestra con puntos, el hidden guarda número puro.
        // Al volver con errores un precio puede venir vacío: el campo queda vacío, no "NaN".
        const conPuntos = valor => Number.isFinite(parseInt(valor)) ? parseInt(valor).toLocaleString('es-CO') : '';
        document.getElementById('editar-precio_compra').value        = precioCompra ?? '';
        document.getElementById('editar-precio_compra_visual').value = conPuntos(precioCompra);
        document.getElementById('editar-precio_venta').value         = precioVenta ?? '';
        document.getElementById('editar-precio_venta_visual').value  = conPuntos(precioVenta);

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
     * Muestra una notificación flotante que desaparece sola a los 5 segundos.
     *
     * Va en el contenedor de avisos del layout (components/avisos-sesion): si salen
     * varias seguidas se apilan en vez de taparse.
     *
     * @param {string} mensaje Texto a mostrar
     * @param {string} tipo    'success' (verde) o 'error' (rojo)
     */
    function mostrarNotificacion(mensaje, tipo = 'success') {
        const noti = document.createElement('div');
        noti.className = `max-w-sm flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-sm font-medium transition-all duration-300 transform translate-x-full ${
            tipo === 'success'
                ? 'bg-green-50 border border-green-200 text-green-700'
                : 'bg-red-50 border border-red-200 text-red-700'
        }`;

        noti.innerHTML = tipo === 'success'
            ? `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
               </svg>`
            : `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
               </svg>`;

        // textContent: el mensaje puede venir del servidor
        const texto = document.createElement('span');
        texto.textContent = mensaje;
        noti.appendChild(texto);

        noti.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
        document.getElementById('contenedor-notificaciones').appendChild(noti);

        // Animar entrada desde la derecha
        requestAnimationFrame(() => noti.classList.remove('translate-x-full'));

        // Desaparecer a los 5 segundos
        setTimeout(() => {
            noti.classList.add('translate-x-full');
            setTimeout(() => noti.remove(), 300);
        }, 5000);
    }

    // ══════════════════════════════════════
    // AJUSTE RÁPIDO DE STOCK
    // ══════════════════════════════════════

    // Fuente de verdad en JS — se actualiza tras cada AJAX exitoso
    const _stockPorProducto = @json(
        $productos->getCollection()->mapWithKeys(fn($p) => [
            $p->id => ['stock' => $p->stock, 'min' => $p->stock_minimo ?? 0]
        ])
    );

    let _ajusteId         = null;
    let _ajusteStock      = 0;
    let _ajusteStockMin   = 0;
    let _ajusteOperacion  = 'entrada';

    const _ajusteLabels = {
        entrada: 'Cantidad a ingresar',
        salida:  'Cantidad a retirar',
    };

    function abrirAjusteStock(id, nombre) {
        const info = _stockPorProducto[id] ?? { stock: 0, min: 0 };
        _ajusteId       = id;
        _ajusteStock    = info.stock;
        _ajusteStockMin = info.min;

        document.getElementById('ajuste-nombre').textContent              = nombre;
        document.getElementById('ajuste-stock-actual-badge').textContent  = info.stock + ' uds.';
        document.getElementById('ajuste-cantidad').value                  = '';
        document.getElementById('ajuste-preview-wrap').classList.add('hidden');
        document.getElementById('ajuste-alerta-salida').classList.add('hidden');
        document.getElementById('ajuste-tipo_movimiento_id').value        = '';
        document.getElementById('ajuste-nuevo_tipo').value                = '';
        document.getElementById('ajuste-nuevo_tipo').classList.add('hidden');
        document.getElementById('ajuste-nota').value                      = '';

        setAjusteOperacion('entrada');

        document.getElementById('modal-ajuste-stock').classList.remove('hidden');
        setTimeout(() => document.getElementById('ajuste-cantidad').focus(), 100);
    }

    function cerrarAjusteStock() {
        document.getElementById('modal-ajuste-stock').classList.add('hidden');
    }

    function toggleNuevoTipo(valor) {
        const input = document.getElementById('ajuste-nuevo_tipo');
        if (valor === 'nuevo') {
            input.classList.remove('hidden');
            input.focus();
        } else {
            input.classList.add('hidden');
            input.value = '';
        }
    }

    function setAjusteOperacion(op) {
        _ajusteOperacion = op;

        ['entrada', 'salida'].forEach(o => {
            const btn = document.getElementById(`ajuste-tab-${o}`);
            const activo = o === op;
            btn.classList.toggle('bg-white',      activo);
            btn.classList.toggle('shadow-sm',     activo);
            btn.classList.toggle('text-gray-900', activo);
            btn.classList.toggle('font-semibold', activo);
            btn.classList.toggle('text-gray-500', !activo);
        });

        document.getElementById('ajuste-label').textContent = _ajusteLabels[op];
        document.getElementById('ajuste-cantidad').value    = '';
        document.getElementById('ajuste-preview-wrap').classList.add('hidden');
        document.getElementById('ajuste-alerta-salida').classList.add('hidden');
        document.getElementById('ajuste-cantidad').focus();
    }

    function actualizarPreviewStock() {
        const cantidad = parseInt(document.getElementById('ajuste-cantidad').value) || 0;
        let nuevo;

        if (_ajusteOperacion === 'entrada') nuevo = _ajusteStock + cantidad;
        else                               nuevo = Math.max(0, _ajusteStock - cantidad);

        const previewWrap = document.getElementById('ajuste-preview-wrap');
        const alertaSalida = document.getElementById('ajuste-alerta-salida');

        if (document.getElementById('ajuste-cantidad').value === '') {
            previewWrap.classList.add('hidden');
            alertaSalida.classList.add('hidden');
            return;
        }

        previewWrap.classList.remove('hidden');
        document.getElementById('ajuste-preview-anterior').textContent = _ajusteStock;

        const spanNuevo = document.getElementById('ajuste-preview-nuevo');
        spanNuevo.textContent = nuevo;

        if (nuevo === 0) spanNuevo.className = 'text-lg font-bold text-red-600';
        else if (nuevo <= _ajusteStockMin) spanNuevo.className = 'text-lg font-bold text-amber-600';
        else spanNuevo.className = 'text-lg font-bold text-emerald-600';

        // Advertencia solo en salida cuando excede el stock
        if (_ajusteOperacion === 'salida' && cantidad > _ajusteStock) {
            alertaSalida.classList.remove('hidden');
        } else {
            alertaSalida.classList.add('hidden');
        }
    }

    function guardarAjusteStock() {
        const cantidad       = parseInt(document.getElementById('ajuste-cantidad').value);
        const tipoSelect     = document.getElementById('ajuste-tipo_movimiento_id').value;
        const nuevoTipo      = document.getElementById('ajuste-nuevo_tipo').value.trim();
        const nota           = document.getElementById('ajuste-nota').value.trim();

        if (isNaN(cantidad) || cantidad <= 0) {
            document.getElementById('ajuste-cantidad').focus();
            mostrarNotificacion('Ingresa una cantidad mayor a 0.', 'error');
            return;
        }

        if (_ajusteOperacion === 'salida' && cantidad > _ajusteStock) {
            document.getElementById('ajuste-cantidad').focus();
            mostrarNotificacion(`No puedes retirar más de ${_ajusteStock} uds. disponibles.`, 'error');
            return;
        }

        // Validar motivo
        if (!tipoSelect && !nuevoTipo) {
            document.getElementById('ajuste-tipo_movimiento_id').focus();
            mostrarNotificacion('Debes seleccionar o escribir un motivo.', 'error');
            return;
        }

        if (tipoSelect === 'nuevo' && !nuevoTipo) {
            document.getElementById('ajuste-nuevo_tipo').focus();
            mostrarNotificacion('Escribe el nombre del nuevo motivo.', 'error');
            return;
        }

        const payload = {
            operacion: _ajusteOperacion,
            cantidad:  cantidad,
            nota:      nota || null,
        };

        if (tipoSelect && tipoSelect !== 'nuevo') {
            payload.tipo_movimiento_id = tipoSelect;
        } else {
            payload.nuevo_tipo = nuevoTipo;
        }

        const btn = document.getElementById('ajuste-btn-guardar');
        btn.disabled    = true;
        btn.textContent = 'Guardando…';

        const baseUrl = '{{ url("admin/productos") }}';

        fetch(`${baseUrl}/${_ajusteId}/stock`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept':       'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                actualizarBadgeStock(_ajusteId, data.stock_nuevo);
                cerrarAjusteStock();
                mostrarNotificacion('Stock actualizado correctamente');
            } else {
                mostrarNotificacion(data.message || 'Error al actualizar', 'error');
            }
        })
        .catch(() => mostrarNotificacion('Error de conexión', 'error'))
        .finally(() => {
            btn.disabled    = false;
            btn.textContent = 'Guardar';
        });
    }

    function actualizarBadgeStock(productoId, nuevoStock) {
        // 1. Actualizar la fuente de verdad JS para que el próximo abrirAjusteStock use el valor correcto
        if (_stockPorProducto[productoId]) {
            _stockPorProducto[productoId].stock = nuevoStock;
        }

        // 2. Actualizar el badge visual en la card
        const btn = document.getElementById(`stock-display-${productoId}`);
        if (!btn) return;

        const span = btn.querySelector('span');
        if (!span) return;

        const min = _stockPorProducto[productoId]?.min ?? 0;
        let color, label;

        if (nuevoStock === 0) {
            color = 'text-red-600';
            label = 'Sin stock';
        } else if (nuevoStock < min) {
            color = 'text-red-600';
            label = `${nuevoStock} ${nuevoStock === 1 ? 'unidad' : 'unidades'}`;
        } else if (nuevoStock === min) {
            color = 'text-amber-600';
            label = `${nuevoStock} ${nuevoStock === 1 ? 'unidad' : 'unidades'}`;
        } else {
            color = 'text-green-600';
            label = `${nuevoStock} ${nuevoStock === 1 ? 'unidad' : 'unidades'}`;
        }

        span.className   = `text-xs font-medium ${color}`;
        span.textContent = label;
    }

    function ocultarErroresCrear() {
        const el = document.getElementById('crear-errores');
        if (!el) return;
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 500);
    }

    // Si hay errores de validación, re-abrir el modal y hacer scroll al bloque de errores.
    // Los errores se quedan hasta cerrarlos con la × o volver a guardar: si se fueran
    // solos, no daría tiempo de leer qué corregir.
    // Los campos de la categoría elegida se muestran sin borrar lo escrito: antes, en
    // pantallas, marco, marca y referencia volvían ocultos y no se podían corregir.
    @if ($errors->any())
        document.addEventListener('DOMContentLoaded', () => {
            if (mostrarCamposSegunCategoria(document.getElementById('crear-categoria_id').value, 'crear')) {
                generarNombrePantalla('crear');
            }

            const modal = document.getElementById('modal-producto');
            modal.classList.remove('hidden');
            const contenedor = modal.querySelector('.overflow-y-auto');
            if (contenedor) contenedor.scrollTop = 0;
        });
    @endif

    // Editar volvió con errores: se reabre el modal de editar de ese producto con lo que
    // se había escrito (old) y con las fotos que ya tiene. Nunca el de crear: ahí, al
    // "corregir y guardar", se crearía otro producto en vez de actualizar este.
    @if ($productoConErrores)
        document.addEventListener('DOMContentLoaded', () => {
            abrirEditarProducto(
                {{ $productoConErrores->id }},
                @js(old('categoria_id')),
                @js(old('tipo_producto_id')),
                @js(old('codigo', '')),
                @js(old('nombre', '')),
                @js(old('descripcion', '')),
                @js(old('precio_compra')),
                @js(old('precio_venta')),
                @js(old('stock')),
                @js(old('stock_minimo')),
                @js(old('marco')),
                @js(old('marca', '')),
                @js(old('referencia', '')),
                @js($productoConErrores->imagenes->map(fn ($i) => ['id' => $i->id, 'path' => $i->path, 'es_principal' => $i->es_principal])),
                true
            );
            const contenedor = document.querySelector('#modal-editar-producto .overflow-y-auto');
            if (contenedor) contenedor.scrollTop = 0;
        });
    @endif
</script>
@endsection
