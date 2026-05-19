<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\ProductoImagen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Service para la lógica de negocio de productos.
 *
 * Maneja: creación, actualización, eliminación lógica y gestión de imágenes.
 *
 * NOTA IMPORTANTE — Storage link:
 * Las imágenes se guardan en storage/app/public/productos/.
 * Para que sean accesibles desde el navegador, ejecutar UNA VEZ:
 *   php artisan storage:link
 * Esto crea el enlace simbólico public/storage → storage/app/public.
 * Luego se muestran con: asset('storage/' . $producto->imagenPrincipal->path)
 */
class ProductoService
{
    /**
     * Crea un nuevo producto con sus imágenes opcionales.
     *
     * Todo dentro de una transacción para que si falla la subida
     * de alguna imagen, el producto no quede creado sin imágenes
     * ni haya imágenes colgadas sin registro en la BD.
     *
     * @param array              $datos    Datos validados del StoreProductoRequest
     * @param UploadedFile[]|null $imagenes Archivos subidos (campo 'imagenes')
     */
    public function crear(array $datos, ?array $imagenes = null): Producto
    {
        return DB::transaction(function () use ($datos, $imagenes) {

            // ── 1. Crear el producto ──
            $producto = Producto::create([
                'categoria_id'  => $datos['categoria_id'],
                'codigo'        => $datos['codigo'] ?? null,
                'nombre'        => $datos['nombre'],
                'descripcion'   => $datos['descripcion'] ?? null,
                'precio_compra' => $datos['precio_compra'],
                'precio_venta'  => $datos['precio_venta'],
                'stock'         => $datos['stock'] ?? 0,
                'stock_minimo'  => $datos['stock_minimo'] ?? 3,
                'estado'        => 'activo',
            ]);

            // ── 2. Guardar imágenes si se enviaron ──
            if (! empty($imagenes)) {
                $this->guardarImagenes($producto, $imagenes, marcarPrimeraComoPrincipal: true);
            }

            return $producto;
        });
    }

    /**
     * Actualiza un producto existente y agrega nuevas imágenes si se enviaron.
     *
     * Las imágenes existentes NO se eliminan — el usuario las borra
     * individualmente con eliminarImagen(). Esto evita pérdidas accidentales.
     *
     * @param Producto           $producto Instancia a actualizar
     * @param array              $datos    Datos validados del UpdateProductoRequest
     * @param UploadedFile[]|null $imagenes Archivos nuevos a agregar (opcional)
     */
    public function actualizar(Producto $producto, array $datos, ?array $imagenes = null): Producto
    {
        return DB::transaction(function () use ($producto, $datos, $imagenes) {

            // Actualizar los campos del producto
            $producto->update([
                'categoria_id'  => $datos['categoria_id'],
                'codigo'        => $datos['codigo'] ?? null,
                'nombre'        => $datos['nombre'],
                'descripcion'   => $datos['descripcion'] ?? null,
                'precio_compra' => $datos['precio_compra'],
                'precio_venta'  => $datos['precio_venta'],
                'stock'         => $datos['stock'],
                'stock_minimo'  => $datos['stock_minimo'] ?? 3,
            ]);

            // Si vienen imágenes nuevas, agregarlas
            if (! empty($imagenes)) {
                // Verificamos si el producto ya tiene imagen principal.
                // Si no tiene, la primera imagen nueva se marcará como principal.
                $tienePrincipal = $producto->imagenes()->where('es_principal', true)->exists();

                $this->guardarImagenes(
                    $producto,
                    $imagenes,
                    marcarPrimeraComoPrincipal: ! $tienePrincipal
                );
            }

            return $producto->fresh(['categoria', 'imagenPrincipal']);
        });
    }

    /**
     * Desactiva un producto (soft delete por estado).
     *
     * No borramos físicamente para conservar el historial de ventas:
     * los VentaItem referencian el producto_id y deben poder consultarse.
     */
    public function eliminar(Producto $producto): void
    {
        $producto->update(['estado' => 'inactivo']);
    }

    /**
     * Elimina una imagen específica de un producto.
     *
     * Pasos:
     * 1. Borrar el archivo físico de storage
     * 2. Si era la imagen principal, asignar otra como principal
     * 3. Borrar el registro de la BD
     *
     * @param ProductoImagen $imagen La imagen a eliminar
     */
    public function eliminarImagen(ProductoImagen $imagen): void
    {
        $eraPrincipal = $imagen->es_principal;
        $productoId   = $imagen->producto_id;

        // Borrar archivo físico del disco 'public' (storage/app/public)
        // delete() no lanza excepción si el archivo no existe, solo retorna false
        Storage::disk('public')->delete($imagen->path);

        // Borrar el registro de la BD
        $imagen->delete();

        // Si la imagen eliminada era la principal, asignar la siguiente disponible
        if ($eraPrincipal) {
            $otraImagen = ProductoImagen::where('producto_id', $productoId)->first();

            if ($otraImagen) {
                $otraImagen->update(['es_principal' => true]);
            }
        }
    }

    /**
     * Guarda un array de archivos en storage y crea los registros en BD.
     *
     * Método privado de apoyo para no repetir lógica entre crear() y actualizar().
     *
     * @param Producto       $producto                Producto dueño de las imágenes
     * @param UploadedFile[] $imagenes                Array de archivos subidos
     * @param bool           $marcarPrimeraComoPrincipal Si la primera imagen debe ser la principal
     */
    private function guardarImagenes(
        Producto $producto,
        array $imagenes,
        bool $marcarPrimeraComoPrincipal = false
    ): void {
        foreach ($imagenes as $indice => $archivo) {
            // store() genera un nombre único automáticamente y retorna la ruta relativa.
            // El disco 'public' guarda en storage/app/public/productos/
            $path = $archivo->store('productos', 'public');

            ProductoImagen::create([
                'producto_id' => $producto->id,
                'path'        => $path,
                // Solo la primera imagen (índice 0) puede ser principal,
                // y solo si se pidió marcarla como tal
                'es_principal' => $marcarPrimeraComoPrincipal && $indice === 0,
            ]);
        }
    }
}
