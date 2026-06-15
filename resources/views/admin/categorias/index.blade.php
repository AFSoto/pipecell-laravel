{{--
    Vista de gestión de categorías.

    Permite listar, crear, editar y desactivar categorías del inventario.
    La creación y edición se hacen en modales para no salir de la página.
    La eliminación usa un form con @method('DELETE') y confirmación JS.
--}}
@extends('layouts.admin')

@section('title', 'Categorías')
@section('page-title', 'Categorías')

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
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Categorías</h2>
        <p class="text-gray-500 mt-1">Agrupa los productos del inventario</p>
    </div>
    <button onclick="document.getElementById('modal-categoria').classList.remove('hidden')"
            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-5 py-3 rounded-xl transition shadow-sm shadow-blue-600/20">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva categoría
    </button>
</div>

{{-- ── Grid de categorías ── --}}
@if ($categorias->isNotEmpty())
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($categorias as $categoria)
            <div class="bg-white rounded-2xl border border-gray-100 p-6 hover:shadow-lg transition group">

                <div class="flex items-start justify-between gap-3">
                    {{-- Ícono decorativo de la categoría --}}
                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </div>

                    {{-- Botones de acción — visibles al hacer hover --}}
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
                        {{-- Botón editar --}}
                        <button onclick="abrirEditarCategoria({{ $categoria->id }}, '{{ addslashes($categoria->nombre) }}', '{{ addslashes($categoria->descripcion ?? '') }}')"
                                class="p-2 rounded-xl hover:bg-blue-50 hover:text-blue-600 text-gray-400 transition"
                                title="Editar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>

                        {{-- Botón eliminar — form con DELETE method y confirmación --}}
                        <form method="POST"
                              action="{{ route('admin.categorias.destroy', $categoria) }}"
                              onsubmit="return confirm('¿Desactivar la categoría «{{ $categoria->nombre }}»? Esto la ocultará del inventario.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="p-2 rounded-xl hover:bg-red-50 hover:text-red-600 text-gray-400 transition"
                                    title="Desactivar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Nombre y descripción --}}
                <div class="mt-4">
                    <h3 class="font-bold text-gray-900 text-base">{{ $categoria->nombre }}</h3>
                    @if ($categoria->descripcion)
                        <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $categoria->descripcion }}</p>
                    @else
                        <p class="text-sm text-gray-400 mt-1 italic">Sin descripción</p>
                    @endif
                </div>

                {{-- Badge con conteo de productos activos --}}
                <div class="mt-4 pt-4 border-t border-gray-50">
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        {{ $categoria->productos_count }}
                        {{ $categoria->productos_count === 1 ? 'producto' : 'productos' }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>
@else
    {{-- Estado vacío --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-16 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z"/>
            </svg>
        </div>
        <p class="text-gray-500 font-medium">No hay categorías registradas</p>
        <p class="text-gray-400 text-sm mt-1">Crea la primera categoría para organizar tus productos</p>
        <button onclick="document.getElementById('modal-categoria').classList.remove('hidden')"
                class="mt-6 inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-5 py-2.5 rounded-xl transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Crear primera categoría
        </button>
    </div>
@endif


{{-- ============================== --}}
{{-- MODAL — Crear categoría         --}}
{{-- ============================== --}}
<div id="modal-categoria" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    {{-- Overlay: clic fuera cierra el modal --}}
    <div class="absolute inset-0 bg-black/50"
         onclick="document.getElementById('modal-categoria').classList.add('hidden')"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">

        <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 rounded-t-2xl flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Nueva categoría</h3>
                <p class="text-sm text-gray-500">Agrega una nueva familia de productos</p>
            </div>
            <button onclick="document.getElementById('modal-categoria').classList.add('hidden')"
                    class="p-2 rounded-xl hover:bg-gray-100 transition">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.categorias.store') }}" class="p-6 space-y-5">
            @csrf

            <div>
                <label class="text-xs text-gray-500 mb-1 block">Nombre *</label>
                <input type="text" name="nombre" required maxlength="100"
                       placeholder="Ej: Accesorios de Carga"
                       value="{{ old('nombre') }}"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>

            <div>
                <label class="text-xs text-gray-500 mb-1 block">Descripción</label>
                <textarea name="descripcion" rows="3"
                          placeholder="Describe qué productos incluye esta categoría..."
                          class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition resize-none">{{ old('descripcion') }}</textarea>
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
                        onclick="document.getElementById('modal-categoria').classList.add('hidden')"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-sm shadow-blue-600/20">
                    Guardar categoría
                </button>
            </div>
        </form>
    </div>
</div>


{{-- ============================== --}}
{{-- MODAL — Editar categoría        --}}
{{-- ============================== --}}
<div id="modal-editar-categoria" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50"
         onclick="document.getElementById('modal-editar-categoria').classList.add('hidden')"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">

        <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 rounded-t-2xl flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Editar categoría</h3>
                <p class="text-sm text-gray-500">Modifica los datos de la categoría</p>
            </div>
            <button onclick="document.getElementById('modal-editar-categoria').classList.add('hidden')"
                    class="p-2 rounded-xl hover:bg-gray-100 transition">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{--
            La action se actualiza dinámicamente con JS al abrir el modal.
            @method('PATCH') indica a Laravel que trate el POST como PATCH.
        --}}
        <form id="form-editar-categoria" method="POST" action="" class="p-6 space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <label class="text-xs text-gray-500 mb-1 block">Nombre *</label>
                <input type="text" id="editar-nombre" name="nombre" required maxlength="100"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>

            <div>
                <label class="text-xs text-gray-500 mb-1 block">Descripción</label>
                <textarea id="editar-descripcion" name="descripcion" rows="3"
                          class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition resize-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button"
                        onclick="document.getElementById('modal-editar-categoria').classList.add('hidden')"
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
    /**
     * Abre el modal de edición y lo llena con los datos de la categoría seleccionada.
     * Se llama desde el botón editar de cada card.
     *
     * @param {number} id          ID de la categoría
     * @param {string} nombre      Nombre actual
     * @param {string} descripcion Descripción actual (puede estar vacía)
     */
    function abrirEditarCategoria(id, nombre, descripcion) {
        // Actualizar la action del form con el ID correcto
        const baseUrl = '{{ url("admin/categorias") }}';
        document.getElementById('form-editar-categoria').action = `${baseUrl}/${id}`;

        // Pre-llenar los campos con los valores actuales
        document.getElementById('editar-nombre').value      = nombre;
        document.getElementById('editar-descripcion').value = descripcion;

        // Mostrar el modal
        document.getElementById('modal-editar-categoria').classList.remove('hidden');

        // Enfocar el campo nombre para mejor UX
        setTimeout(() => document.getElementById('editar-nombre').focus(), 50);
    }

    // Auto-ocultar alertas flash después de 4 segundos
    ['alert-success', 'alert-error'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            setTimeout(() => {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            }, 4000);
        }
    });

    // Si hay errores de validación, re-abrir el modal de creación automáticamente
    // para que el usuario vea los errores sin tener que volver a abrirlo
    @if ($errors->any())
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('modal-categoria').classList.remove('hidden');
        });
    @endif
</script>
@endsection
