<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoriaRequest;
use App\Http\Requests\UpdateCategoriaRequest;
use App\Models\Categoria;
use App\Services\CategoriaService;

/**
 * Controlador de Categorías.
 *
 * Responsabilidad única: recibir la petición HTTP, delegar al Service
 * y devolver la respuesta. Sin lógica de negocio interna.
 */
class CategoriaController extends Controller
{
    /**
     * Inyección del service por constructor.
     * Laravel resuelve automáticamente la dependencia (IoC container).
     */
    public function __construct(
        private CategoriaService $categoriaService
    ) {}

    /**
     * Muestra el listado de categorías activas con conteo de productos.
     *
     * withCount('productos') agrega el campo virtual "productos_count"
     * sin cargar todos los productos en memoria — solo ejecuta un COUNT en SQL.
     */
    public function index()
    {
        // Solo mostramos las activas; las inactivas se ocultan del panel
        $categorias = Categoria::activas()
            ->withCount(['productos' => fn ($q) => $q->where('estado', 'activo')])
            ->orderBy('nombre')
            ->get();

        return view('admin.categorias.index', compact('categorias'));
    }

    /**
     * Guarda una nueva categoría.
     *
     * StoreCategoriaRequest valida antes de entrar aquí.
     */
    public function store(StoreCategoriaRequest $request)
    {
        try {
            $this->categoriaService->crear($request->validated());

            return redirect()
                ->route('admin.categorias.index')
                ->with('success', 'Categoría creada con éxito.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.categorias.index')
                ->with('error', 'Error al crear la categoría: ' . $e->getMessage());
        }
    }

    /**
     * Actualiza una categoría existente.
     *
     * Route model binding: Laravel busca la Categoria por ID automáticamente.
     */
    public function update(UpdateCategoriaRequest $request, Categoria $categoria)
    {
        try {
            $this->categoriaService->actualizar($categoria, $request->validated());

            return redirect()
                ->route('admin.categorias.index')
                ->with('success', "Categoría \"{$categoria->nombre}\" actualizada con éxito.");
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.categorias.index')
                ->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Desactiva una categoría (soft delete por estado).
     *
     * El Service lanza RuntimeException si tiene productos activos.
     * El controller la captura y la convierte en mensaje de error para el usuario.
     */
    public function destroy(Categoria $categoria)
    {
        try {
            $this->categoriaService->eliminar($categoria);

            return redirect()
                ->route('admin.categorias.index')
                ->with('success', "Categoría \"{$categoria->nombre}\" desactivada.");
        } catch (\RuntimeException $e) {
            // Error de negocio esperado (tiene productos activos)
            return redirect()
                ->route('admin.categorias.index')
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.categorias.index')
                ->with('error', 'Error inesperado: ' . $e->getMessage());
        }
    }
}
