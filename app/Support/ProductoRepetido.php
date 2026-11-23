<?php

namespace App\Support;

use App\Models\Producto;

/**
 * Regla de "producto repetido": un producto activo choca con otro activo si tienen el
 * mismo nombre, categoría, tipo, marco y marca. Así pueden convivir dos "Batería iPhone 14"
 * de distinta marca. Un campo vacío cuenta como un valor más: dos con el mismo nombre y sin
 * marca chocan.
 *
 * La revisan dos veces:
 *   - la validación (Concerns\ValidaNombreUnicoProducto), para avisar en el formulario;
 *   - ProductoService, justo antes de guardar y con la categoría bloqueada: entre la
 *     validación y el guardado pudo entrar otro igual (un doble toque en Guardar).
 *
 * Es la única protección: la base de datos no tiene índice único de productos (ver la
 * migración drop_unique_index_from_productos_table). MySQL compara sin distinguir
 * mayúsculas ni tildes (utf8mb4_unicode_ci): "Deji" y "DEJI" son la misma marca.
 */
final class ProductoRepetido
{
    /**
     * El mensaje de error si estos datos chocan con un producto activo; null si no.
     *
     * @param  array  $datos  nombre, categoria_id, tipo_producto_id, marco y marca, como los manda el formulario
     * @param  int|null  $ignorarId  El producto que se edita (null al crear), para que no choque consigo mismo
     * @param  bool  $bloquear  Dentro de una transacción: lee lo último confirmado (SELECT ... FOR UPDATE)
     */
    public static function buscar(array $datos, ?int $ignorarId = null, bool $bloquear = false): ?string
    {
        $nombre = $datos['nombre'];
        $marca  = is_string($datos['marca'] ?? null) ? $datos['marca'] : null;
        $marco  = $datos['marco'] ?? null;

        $query = Producto::activos()
            ->where('categoria_id', $datos['categoria_id'] ?? null)
            ->where('nombre', $nombre)
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId));

        if (! empty($datos['tipo_producto_id'])) {
            $query->where('tipo_producto_id', $datos['tipo_producto_id']);
        } else {
            $query->whereNull('tipo_producto_id');
        }

        if (is_null($marco)) {
            $query->whereNull('marco');
        } else {
            $query->where('marco', (bool) $marco);
        }

        // Sin marca = NULL; también '' por si algún registro viejo quedó así
        if (filled($marca)) {
            $query->where('marca', $marca);
        } else {
            $query->where(fn ($q) => $q->whereNull('marca')->orWhere('marca', ''));
        }

        // value() y no exists(): con exists() el FOR UPDATE quedaría dentro de una subconsulta
        $repetido = $bloquear
            ? $query->lockForUpdate()->value('id') !== null
            : $query->exists();

        return $repetido ? self::mensaje($nombre, $marca, $marco) : null;
    }

    /**
     * Dice contra qué chocó, para que se sepa qué cambiar:
     * «Ya existe «Batería iPhone 14» de la marca «Deji».»
     * «Ya existe «Batería iPhone 14» sin marca. Si es de otra marca, escríbela en el campo Marca.»
     * En pantallas agrega si es con marco o sin marco.
     */
    private static function mensaje(string $nombre, ?string $marca, mixed $marco): string
    {
        $mensaje = "Ya existe «{$nombre}» " . (filled($marca) ? "de la marca «{$marca}»" : 'sin marca');

        if (! is_null($marco)) {
            $mensaje .= $marco ? ', con marco' : ', sin marco';
        }

        return $mensaje . (filled($marca) ? '.' : '. Si es de otra marca, escríbela en el campo Marca.');
    }
}
