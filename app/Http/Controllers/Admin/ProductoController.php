<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\TipoMovimientoStock;
use App\Models\TipoProducto;
use App\Services\ProductoService;
use Illuminate\Http\Request;

/**
 * Controlador de Productos.
 *
 * Responsabilidad única: recibir peticiones HTTP, delegar al Service
 * y devolver respuestas. Sin lógica de negocio interna.
 */
class ProductoController extends Controller
{
    public function __construct(
        private ProductoService $productoService
    ) {}

    /**
     * Lista productos con filtros: categoría, búsqueda y stock bajo.
     *
     * Usa eager loading (with) para evitar N+1 queries al mostrar
     * categoría e imagen principal de cada producto en el grid.
     */
    public function index(Request $request)
    {
        // Leer filtros de la URL
        $categoriaFiltro = $request->input('categoria');
        $buscar          = $request->input('buscar');
        $stockBajo       = $request->boolean('stock_bajo'); // convierte '1'/'' a true/false

        $query = Producto::activos()
            ->with(['categoria', 'tipoProducto', 'imagenPrincipal'])
            ->when($categoriaFiltro, fn ($q) => $q->deCategoria((int) $categoriaFiltro))
            ->when(strlen($buscar ?? '') >= 2, function ($q) use ($buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('codigo', 'like', "%{$buscar}%");
                });
            })
            ->when($stockBajo, fn ($q) => $q->stockBajo())
            ->orderBy('nombre');

        // Paginamos de 12 en 12 para el grid de 4 columnas (3 filas completas)
        $productos = $query->paginate(12)->withQueryString();

        $categorias         = Categoria::activas()->orderBy('nombre')->get();
        $tiposProducto      = TipoProducto::orderBy('nombre')->get();
        $tiposMovimiento    = TipoMovimientoStock::orderBy('nombre')->get();

        return view('admin.productos.index', compact('productos', 'categorias', 'tiposProducto', 'tiposMovimiento'));
    }

    /**
     * Guarda un nuevo producto con sus imágenes.
     *
     * $request->file('imagenes') retorna null si no se enviaron archivos,
     * el Service lo maneja correctamente.
     */
    public function store(StoreProductoRequest $request)
    {
        try {
            $this->productoService->crear(
                $request->validated(),
                $request->file('imagenes') // array de UploadedFile o null
            );

            return redirect()
                ->route('admin.productos.index')
                ->with('success', 'Producto creado con éxito.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.productos.index')
                ->with('error', 'Error al crear el producto: ' . $e->getMessage());
        }
    }

    /**
     * Actualiza un producto y agrega nuevas imágenes si se enviaron.
     */
    public function update(UpdateProductoRequest $request, Producto $producto)
    {
        try {
            $this->productoService->actualizar(
                $producto,
                $request->validated(),
                $request->file('imagenes')
            );

            return redirect()
                ->route('admin.productos.index')
                ->with('success', "Producto \"{$producto->nombre}\" actualizado con éxito.");
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.productos.index')
                ->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Ajusta el stock vía AJAX y deja trazabilidad en movimientos_stock.
     * Delega toda la lógica al ProductoService (transacción incluida).
     */
    public function ajustarStock(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'operacion'          => ['required', 'in:entrada,salida'],
            'cantidad'           => ['required', 'integer', 'min:1'],
            'tipo_movimiento_id' => ['nullable', 'exists:tipos_movimiento_stock,id'],
            'nuevo_tipo'         => ['nullable', 'string', 'max:100'],
            'nota'               => ['nullable', 'string', 'max:500'],
        ]);

        if (empty($data['tipo_movimiento_id']) && empty($data['nuevo_tipo'])) {
            return response()->json([
                'success' => false,
                'message' => 'Debes seleccionar o escribir un motivo del ajuste.',
            ], 422);
        }

        try {
            $this->productoService->ajustarStock($producto, $data, $request->user()->id);
            $producto->refresh();

            return response()->json([
                'success'     => true,
                'stock_nuevo' => $producto->stock,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al ajustar el stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Desactiva un producto (soft delete por estado).
     */
    public function destroy(Producto $producto)
    {
        try {
            $nombre = $producto->nombre;
            $this->productoService->eliminar($producto);

            return redirect()
                ->route('admin.productos.index')
                ->with('success', "Producto \"{$nombre}\" desactivado.");
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.productos.index')
                ->with('error', 'Error al desactivar: ' . $e->getMessage());
        }
    }

    /**
     * Elimina una imagen de producto vía AJAX.
     *
     * Retorna JSON para que el frontend pueda quitar el thumbnail
     * sin recargar la página.
     */
    public function eliminarImagen(ProductoImagen $imagen)
    {
        try {
            $this->productoService->eliminarImagen($imagen);

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la imagen: ' . $e->getMessage(),
            ], 500);
        }
    }
}
