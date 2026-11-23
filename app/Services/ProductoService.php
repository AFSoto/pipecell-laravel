<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\TipoMovimientoStock;
use App\Support\FotoProducto;
use App\Support\ProductoRepetido;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
    /** Fotos del último crear() o actualizar() que quedaron con su peso original (ver guardarFoto()). */
    private int $fotosSinOptimizar = 0;

    /**
     * Crea un nuevo producto con sus imágenes opcionales.
     *
     * Las fotos se convierten y se escriben antes de abrir la transacción: tardan de 1 a
     * 4 s cada una, y así la transacción (y el bloqueo de evitarRepetido()) dura
     * milisegundos. Si después falla algo, se borran: no quedan archivos sueltos ni un
     * producto creado sin sus fotos.
     *
     * @param array              $datos    Datos validados del StoreProductoRequest
     * @param UploadedFile[]|null $imagenes Archivos subidos (campo 'imagenes')
     *
     * @throws ValidationException si mientras tanto se guardó otro igual (ver evitarRepetido())
     */
    public function crear(array $datos, ?array $imagenes = null): Producto
    {
        $fotos = $this->guardarFotos($imagenes ?? []);

        try {
            return DB::transaction(function () use ($datos, $fotos) {
                $this->evitarRepetido($datos);

                $producto = $this->crearProducto($datos);
                $this->registrarFotos($producto, $fotos);

                return $producto;
            });
        } catch (\Throwable $e) {
            $this->borrarFotos($fotos);

            throw $e;
        }
    }

    /** Inserta el producto con los datos validados. */
    private function crearProducto(array $datos): Producto
    {
        return Producto::create([
            'categoria_id'     => $datos['categoria_id'],
            'tipo_producto_id' => $datos['tipo_producto_id'] ?? null,
            'codigo'           => $datos['codigo'] ?? null,
            'nombre'           => $datos['nombre'],
            'descripcion'      => $datos['descripcion'] ?? null,
            'marco'            => $datos['marco'] ?? null,
            'marca'            => $datos['marca'] ?? null,
            'referencia'       => $datos['referencia'] ?? null,
            'precio_compra'    => $datos['precio_compra'] ?? null, // opcional: null = sin precio de compra
            'precio_venta'     => $datos['precio_venta'],
            'stock'            => $datos['stock'] ?? 0,
            'stock_minimo'     => $datos['stock_minimo'] ?? 3,
            'estado'           => 'activo',
        ]);
    }

    /**
     * Actualiza un producto existente y agrega nuevas imágenes si se enviaron.
     *
     * Las imágenes existentes NO se eliminan — el usuario las borra
     * individualmente con eliminarImagen(). Esto evita pérdidas accidentales.
     * Las fotos nuevas se guardan igual que en crear(): antes de la transacción, y se
     * borran si después falla algo.
     *
     * @param Producto           $producto Instancia a actualizar
     * @param array              $datos    Datos validados del UpdateProductoRequest
     * @param UploadedFile[]|null $imagenes Archivos nuevos a agregar (opcional)
     *
     * @throws ValidationException si mientras tanto se guardó otro igual (ver evitarRepetido())
     */
    public function actualizar(Producto $producto, array $datos, ?array $imagenes = null): Producto
    {
        $fotos = $this->guardarFotos($imagenes ?? []);

        try {
            return DB::transaction(function () use ($producto, $datos, $fotos) {
                $this->evitarRepetido($datos, $producto->id);

                $this->actualizarProducto($producto, $datos);

                // Las fotos nuevas se agregan y la primera pasa a ser la principal
                if ($fotos !== []) {
                    $producto->imagenes()->where('es_principal', true)->update(['es_principal' => false]);
                    $this->registrarFotos($producto, $fotos);
                }

                return $producto->fresh(['categoria', 'imagenPrincipal']);
            });
        } catch (\Throwable $e) {
            $this->borrarFotos($fotos);

            throw $e;
        }
    }

    /** Cambia los campos del producto por los validados. */
    private function actualizarProducto(Producto $producto, array $datos): void
    {
        $producto->update([
            'categoria_id'     => $datos['categoria_id'],
            'tipo_producto_id' => $datos['tipo_producto_id'] ?? null,
            'codigo'           => $datos['codigo'] ?? null,
            'nombre'           => $datos['nombre'],
            'descripcion'      => $datos['descripcion'] ?? null,
            'marco'            => $datos['marco'] ?? null,
            'marca'            => $datos['marca'] ?? null,
            'referencia'       => $datos['referencia'] ?? null,
            'precio_compra'    => $datos['precio_compra'] ?? null, // vaciar el campo deja el producto sin precio de compra
            'precio_venta'     => $datos['precio_venta'],
            'stock'            => $datos['stock'],
            'stock_minimo'     => $datos['stock_minimo'] ?? 3,
        ]);
    }

    /**
     * Cuántas fotos del último crear() o actualizar() no se pudieron convertir y quedaron
     * con su peso original. El controlador lo avisa; la causa queda en laravel.log.
     */
    public function fotosSinOptimizar(): int
    {
        return $this->fotosSinOptimizar;
    }

    /**
     * Vuelve a revisar que no esté repetido (ver ProductoRepetido), ya dentro de la
     * transacción y justo antes de escribir. La validación lo revisó antes, pero entre la
     * validación y este momento pudo guardarse otro igual: un doble toque en Guardar manda
     * dos envíos casi juntos, y la base no tiene índice único que lo frene.
     *
     * Bloquear la fila de la categoría pone en fila los guardados de esa categoría: el
     * segundo espera a que el primero termine (milisegundos: las fotos ya se guardaron
     * antes) y entonces lo ve.
     *
     * @throws ValidationException con el mismo mensaje que da la validación
     */
    private function evitarRepetido(array $datos, ?int $ignorarId = null): void
    {
        Categoria::whereKey($datos['categoria_id'])->lockForUpdate()->first();

        $mensaje = ProductoRepetido::buscar($datos, $ignorarId, bloquear: true);

        if ($mensaje !== null) {
            throw ValidationException::withMessages(['nombre' => $mensaje]);
        }
    }

    /**
     * Ajusta el stock de un producto y registra el movimiento en una sola transacción.
     *
     * Operaciones:
     *   - 'entrada' : suma cantidad al stock actual
     *   - 'salida'  : resta cantidad (nunca baja de 0)
     *
     * Si se envía 'nuevo_tipo', crea el TipoMovimientoStock antes de continuar
     * (usando firstOrCreate para idempotencia). Todo en la misma transacción.
     *
     * @param Producto $producto   Producto a ajustar
     * @param array    $datos      Datos validados del request
     * @param int      $userId     ID del usuario autenticado
     */
    public function ajustarStock(Producto $producto, array $datos, int $userId): MovimientoStock
    {
        return DB::transaction(function () use ($producto, $datos, $userId) {

            // 1. Resolver tipo de movimiento — existente o nuevo creado al vuelo
            if (! empty($datos['nuevo_tipo'])) {
                $tipo             = TipoMovimientoStock::firstOrCreate(['nombre' => trim($datos['nuevo_tipo'])]);
                $tipoMovimientoId = $tipo->id;
            } else {
                $tipoMovimientoId = $datos['tipo_movimiento_id'];
            }

            // 2. Releer el producto dentro de la transacción con bloqueo de fila.
            // Evita race conditions donde otro proceso (p.ej. una venta) modificó
            // el stock entre el route model binding y este momento.
            $producto      = Producto::lockForUpdate()->findOrFail($producto->id);
            $stockAnterior = (int) $producto->stock;

            if ($datos['operacion'] === 'salida' && $datos['cantidad'] > $stockAnterior) {
                throw new \InvalidArgumentException(
                    "No puedes retirar más unidades de las disponibles. Stock actual: {$stockAnterior} uds."
                );
            }

            // 3. Aplicar el cambio en la tabla productos
            if ($datos['operacion'] === 'entrada') {
                $producto->increment('stock', $datos['cantidad']);
            } else {
                $producto->decrement('stock', $datos['cantidad']);
            }

            $producto->refresh();
            $stockNuevo = (int) $producto->stock;

            // 4. Cantidad con signo: positivo = entrada, negativo = salida
            $cantidadSignada = $datos['operacion'] === 'entrada'
                ? $datos['cantidad']
                : -$datos['cantidad'];

            // 5. Registrar el movimiento
            return MovimientoStock::create([
                'producto_id'        => $producto->id,
                'user_id'            => $userId,
                'tipo_movimiento_id' => $tipoMovimientoId,
                'cantidad'           => $cantidadSignada,
                'stock_anterior'     => $stockAnterior,
                'stock_nuevo'        => $stockNuevo,
                'nota'               => $datos['nota'] ?? null,
            ]);
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
        $path         = $imagen->path;

        // Las operaciones de BD van dentro de la transacción.
        // El archivo físico se borra DESPUÉS, una vez confirmada la transacción,
        // para evitar que quede huérfano si la BD falla.
        DB::transaction(function () use ($imagen, $eraPrincipal, $productoId) {

            $imagen->delete();

            if ($eraPrincipal) {
                $otraImagen = ProductoImagen::where('producto_id', $productoId)->first();

                if ($otraImagen) {
                    $otraImagen->update(['es_principal' => true]);
                }
            }
        });

        // delete() no lanza excepción si el archivo no existe, solo retorna false
        Storage::disk('public')->delete($path);
    }

    /**
     * Convierte y escribe las fotos en storage/app/public/productos/, en el orden en que
     * llegaron, y retorna sus rutas. Todavía no tienen registro en la BD (registrarFotos()).
     *
     * Si una no se puede escribir, borra las que ya escribió y lanza la excepción: no
     * quedan archivos sueltos.
     *
     * @param  UploadedFile[]  $archivos
     * @return string[]
     */
    private function guardarFotos(array $archivos): array
    {
        $this->fotosSinOptimizar = 0;
        $rutas = [];

        try {
            foreach ($archivos as $archivo) {
                $rutas[] = $this->guardarFoto($archivo);
            }
        } catch (\Throwable $e) {
            $this->borrarFotos($rutas);

            throw $e;
        }

        return $rutas;
    }

    /** Crea los registros de las fotos ya guardadas; la primera queda como principal. */
    private function registrarFotos(Producto $producto, array $rutas): void
    {
        foreach ($rutas as $indice => $ruta) {
            ProductoImagen::create([
                'producto_id'  => $producto->id,
                'path'         => $ruta,
                'es_principal' => $indice === 0,
            ]);
        }
    }

    /** Borra fotos recién escritas cuyo guardado no se completó. */
    private function borrarFotos(array $rutas): void
    {
        if ($rutas !== []) {
            Storage::disk('public')->delete($rutas);
        }
    }

    /**
     * Guarda la foto convertida a WebP liviano (ver FotoProducto) en
     * storage/app/public/productos/ y retorna su ruta relativa.
     *
     * Si no se puede convertir (un formato que GD no lee, un GD sin WebP, no alcanza la
     * memoria...), se guarda el archivo original como antes, el error queda en laravel.log
     * y se cuenta en fotosSinOptimizar: la foto nunca se pierde y el producto se guarda igual.
     */
    private function guardarFoto(UploadedFile $archivo): string
    {
        try {
            $path = 'productos/' . Str::random(40) . '.webp';

            if (! Storage::disk('public')->put($path, FotoProducto::aWebp($archivo->getRealPath()))) {
                throw new \RuntimeException("No se pudo escribir {$path}.");
            }

            return $path;
        } catch (\Throwable $e) {
            report($e);
            $this->fotosSinOptimizar++;

            // store() genera un nombre único con la extensión original; si ni así se puede
            // escribir, guardarFotos() borra las anteriores y el controlador avisa del error
            return $archivo->store('productos', 'public')
                ?: throw new \RuntimeException('No se pudo guardar la foto en el servidor.');
        }
    }
}
