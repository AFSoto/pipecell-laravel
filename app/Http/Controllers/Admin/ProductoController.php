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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cookie;

/**
 * Controlador de Productos.
 *
 * Responsabilidad única: recibir peticiones HTTP, delegar al Service
 * y devolver respuestas. Sin lógica de negocio interna.
 */
class ProductoController extends Controller
{
    /**
     * Productos por página según la vista: las tarjetas llenan 3 filas de 4 columnas;
     * la lista es compacta y muestra muchos más.
     */
    private const POR_PAGINA = ['tarjetas' => 12, 'lista' => 100];

    /** Cookie con la vista elegida en este equipo (PC, iPad...). */
    private const COOKIE_VISTA = 'productos_vista';

    /** Lo que se conserva de la lista al volver después de editar o desactivar. */
    private const FILTROS_LISTA = ['categoria', 'buscar', 'stock_bajo', 'vista', 'page'];

    public function __construct(
        private ProductoService $productoService
    ) {}

    /**
     * Lista productos con filtros: categoría, búsqueda y stock bajo, en tarjetas o
     * en lista (ver vistaElegida()).
     *
     * Usa eager loading (with) para evitar N+1 queries: categoría, tipo, imagen
     * principal y todas las imágenes (las usa el botón Editar de cada producto).
     */
    public function index(Request $request)
    {
        // Leer filtros de la URL
        $categoriaFiltro = $request->input('categoria');
        $buscar          = $request->input('buscar');
        $stockBajo       = $request->boolean('stock_bajo'); // convierte '1'/'' a true/false
        $vista           = $this->vistaElegida($request);

        $query = Producto::activos()
            ->with(['categoria', 'tipoProducto', 'imagenPrincipal', 'imagenes'])
            ->when($categoriaFiltro, fn ($q) => $q->deCategoria((int) $categoriaFiltro))
            ->when(strlen($buscar ?? '') >= 2, function ($q) use ($buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('marca', 'like', "%{$buscar}%")
                        ->orWhere('codigo', 'like', "%{$buscar}%");
                });
            })
            ->when($stockBajo, fn ($q) => $q->stockBajo())
            // Puede haber varios con el mismo nombre (distinta marca): salen juntos y
            // siempre en el mismo orden, así la paginación no repite ni salta ninguno
            ->orderBy('nombre')
            ->orderBy('marca')
            ->orderBy('id');

        // Los enlaces de página llevan la vista: si otra pestaña cambia la cookie,
        // "siguiente" sigue en esta vista y con su cantidad por página
        $productos = $query->paginate(self::POR_PAGINA[$vista])->withQueryString()->appends('vista', $vista);

        // Página que ya no existe (se desactivó lo que había, o un enlace viejo): a la
        // última que sí. Sin resultados, lastPage() es 1 y ahí se muestra el aviso vacío.
        if ($productos->isEmpty() && $productos->currentPage() > 1) {
            return redirect()->to($productos->url($productos->lastPage()));
        }

        $categorias         = Categoria::activas()->orderBy('nombre')->get();
        $tiposProducto      = TipoProducto::orderBy('nombre')->get();
        $tiposMovimiento    = TipoMovimientoStock::orderBy('nombre')->get();

        // Editar volvió con errores: se reabre el modal de ese producto con lo que se
        // había escrito y con sus fotos (ver UpdateProductoRequest)
        $idConErrores       = $request->session()->get(UpdateProductoRequest::PRODUCTO_CON_ERRORES);
        $productoConErrores = $idConErrores && $request->session()->get('errors')?->hasBag('editarProducto')
            ? Producto::with('imagenes')->find($idConErrores)
            : null;

        return view('admin.productos.index', compact(
            'productos', 'categorias', 'tiposProducto', 'tiposMovimiento', 'vista', 'productoConErrores'
        ));
    }

    /**
     * Vista elegida: 'tarjetas' o 'lista'.
     *
     * La del enlace (?vista=) si es válida, y queda guardada en una cookie de este
     * equipo por un año. Si no viene, la de la cookie; si no hay, tarjetas.
     *
     * Se decide aquí, y no con localStorage, porque cambia cuántos productos van por
     * página. Así la página sale dibujada en su vista desde el principio, sin parpadeo.
     */
    private function vistaElegida(Request $request): string
    {
        $pedida = $request->query('vista');

        if (is_string($pedida) && isset(self::POR_PAGINA[$pedida])) {
            Cookie::queue(self::COOKIE_VISTA, $pedida, 60 * 24 * 365);

            return $pedida;
        }

        $guardada = $request->cookie(self::COOKIE_VISTA);

        return is_string($guardada) && isset(self::POR_PAGINA[$guardada]) ? $guardada : 'tarjetas';
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

            return $this->volverALaLista($request, $producto->id)
                ->with('success', "Producto \"{$producto->nombre_con_marca}\" actualizado con éxito.");
        } catch (\Exception $e) {
            return $this->volverALaLista($request, $producto->id)
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
    public function destroy(Request $request, Producto $producto)
    {
        try {
            $nombre = $producto->nombre_con_marca;
            $this->productoService->eliminar($producto);

            // Sin ancla: el producto ya no está en la lista
            return $this->volverALaLista($request)
                ->with('success', "Producto \"{$nombre}\" desactivado.");
        } catch (\Exception $e) {
            return $this->volverALaLista($request, $producto->id)
                ->with('error', 'Error al desactivar: ' . $e->getMessage());
        }
    }

    /**
     * Vuelve a la lista como estaba: filtros, vista y página (el campo oculto "volver"
     * trae la query de la página donde se editó o desactivó). Con $productoId baja
     * hasta ese producto, que la lista resalta.
     *
     * Solo se toma la query, y solo los filtros conocidos con valores simples: la URL
     * base es siempre la de la lista, así que no se puede redirigir a otro sitio.
     */
    private function volverALaLista(Request $request, ?int $productoId = null): RedirectResponse
    {
        parse_str((string) $request->input('volver', ''), $query);

        $filtros = array_filter(Arr::only($query, self::FILTROS_LISTA), 'is_scalar');
        $url     = route('admin.productos.index', $filtros);

        return redirect()->to($productoId ? "{$url}#producto-{$productoId}" : $url);
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
