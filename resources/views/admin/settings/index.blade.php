@extends('layouts.admin')

@section('title', 'Configuración')
@section('page-title', 'Configuración')

@section('content')

{{-- Flash messages --}}
@if (session('success'))
    <div id="flash-success"
         class="mb-6 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div id="flash-error"
         class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm">
        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('error') }}
    </div>
@endif

{{-- ══════════════════════════════════════════════════════════════
     SECCIÓN: Tipos de Producto
     ══════════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100">

    {{-- Encabezado de sección --}}
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Tipos de producto</h2>
            <p class="text-xs text-gray-400 mt-0.5">Genérico, Original, Semi-original, etc.</p>
        </div>
        <button onclick="abrirModalCrear()"
                class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-xl transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Agregar tipo
        </button>
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs font-semibold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                    <th class="px-6 py-3 text-left">Nombre</th>
                    <th class="px-6 py-3 text-center">Productos</th>
                    <th class="px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($tiposProducto as $tipo)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-3 font-medium text-gray-900">
                            {{ $tipo->nombre }}
                        </td>
                        <td class="px-6 py-3 text-center">
                            <span class="inline-flex items-center justify-center text-xs font-semibold px-2.5 py-0.5 rounded-full
                                {{ $tipo->productos_count > 0 ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-400' }}">
                                {{ $tipo->productos_count }}
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Editar --}}
                                <button onclick="abrirModalEditar({{ $tipo->id }}, '{{ addslashes($tipo->nombre) }}')"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition"
                                        title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>

                                {{-- Eliminar --}}
                                <button onclick="abrirModalEliminar({{ $tipo->id }}, '{{ addslashes($tipo->nombre) }}', {{ $tipo->productos_count }})"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition"
                                        title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-12 text-center text-gray-400 text-sm">
                            No hay tipos de producto registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════════════
     MODAL: Crear tipo de producto
     ══════════════════════════════════════════════════════════════ --}}
<div id="modal-crear" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="cerrarModalCrear()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm">

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Nuevo tipo de producto</h3>
            <button onclick="cerrarModalCrear()" class="p-1 rounded-lg hover:bg-gray-100 transition text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.settings.tipo-producto.store') }}" class="p-6 space-y-4">
            @csrf

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div>
                <label class="text-xs text-gray-500 mb-1 block">Nombre *</label>
                <input type="text" name="nombre" id="crear-nombre" required maxlength="100"
                       value="{{ old('nombre') }}"
                       placeholder="Ej: Original, Genérico, Semi-original"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                       autofocus>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="cerrarModalCrear()"
                        class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium transition">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════════════
     MODAL: Editar tipo de producto
     ══════════════════════════════════════════════════════════════ --}}
<div id="modal-editar" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="cerrarModalEditar()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm">

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Editar tipo de producto</h3>
            <button onclick="cerrarModalEditar()" class="p-1 rounded-lg hover:bg-gray-100 transition text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="form-editar" method="POST" action="" class="p-6 space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="text-xs text-gray-500 mb-1 block">Nombre *</label>
                <input type="text" name="nombre" id="editar-nombre" required maxlength="100"
                       placeholder="Ej: Original, Genérico, Semi-original"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="cerrarModalEditar()"
                        class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium transition">
                    Actualizar
                </button>
            </div>
        </form>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════════════
     MODAL: Confirmar eliminación
     ══════════════════════════════════════════════════════════════ --}}
<div id="modal-eliminar" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="cerrarModalEliminar()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">

        <div class="flex flex-col items-center text-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">Eliminar tipo</h3>
                <p class="text-sm text-gray-500 mt-1">
                    ¿Eliminar <span id="eliminar-nombre" class="font-semibold text-gray-700"></span>?
                    Esta acción no se puede deshacer.
                </p>
            </div>
            <p id="eliminar-advertencia" class="hidden text-xs bg-amber-50 border border-amber-200 text-amber-700 rounded-xl px-3 py-2 w-full">
                Este tipo tiene productos asociados y no podrá eliminarse.
            </p>
        </div>

        <form id="form-eliminar" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="flex gap-3">
                <button type="button" onclick="cerrarModalEliminar()"
                        class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button id="eliminar-btn" type="submit"
                        class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-medium transition">
                    Eliminar
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const baseUrlTipoProducto = "{{ url('admin/settings/tipo-producto') }}";

    // ── Flash auto-hide ──
    ['flash-success', 'flash-error'].forEach(id => {
        const el = document.getElementById(id);
        if (el) setTimeout(() => {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 3500);
    });

    // ── Modal Crear ──
    function abrirModalCrear() {
        document.getElementById('modal-crear').classList.remove('hidden');
        document.getElementById('crear-nombre').focus();
    }
    function cerrarModalCrear() {
        document.getElementById('modal-crear').classList.add('hidden');
    }

    // ── Modal Editar ──
    function abrirModalEditar(id, nombre) {
        const modal = document.getElementById('modal-editar');
        document.getElementById('form-editar').action = `${baseUrlTipoProducto}/${id}`;
        document.getElementById('editar-nombre').value = nombre;
        modal.classList.remove('hidden');
        document.getElementById('editar-nombre').focus();
    }
    function cerrarModalEditar() {
        document.getElementById('modal-editar').classList.add('hidden');
    }

    // ── Modal Eliminar ──
    function abrirModalEliminar(id, nombre, productosCount) {
        const modal       = document.getElementById('modal-eliminar');
        const advertencia = document.getElementById('eliminar-advertencia');
        const btn         = document.getElementById('eliminar-btn');

        document.getElementById('form-eliminar').action = `${baseUrlTipoProducto}/${id}`;
        document.getElementById('eliminar-nombre').textContent = nombre;

        if (productosCount > 0) {
            advertencia.classList.remove('hidden');
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            advertencia.classList.add('hidden');
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }

        modal.classList.remove('hidden');
    }
    function cerrarModalEliminar() {
        document.getElementById('modal-eliminar').classList.add('hidden');
    }

    // ── Abrir modal crear si hubo error de validación ──
    @if ($errors->any())
        document.addEventListener('DOMContentLoaded', () => {
            abrirModalCrear();
        });
    @endif

    // ── Cerrar modales con Escape ──
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            cerrarModalCrear();
            cerrarModalEditar();
            cerrarModalEliminar();
        }
    });
</script>
@endsection
