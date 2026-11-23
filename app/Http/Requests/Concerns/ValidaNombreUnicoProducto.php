<?php

namespace App\Http\Requests\Concerns;

use App\Models\Producto;

/**
 * Regla de "producto repetido", compartida por StoreProductoRequest y UpdateProductoRequest.
 *
 * Un producto activo choca con otro activo si tienen el mismo nombre, categoría, tipo,
 * marco y marca. Así pueden convivir dos "Batería iPhone 14" de distinta marca.
 * Un campo vacío cuenta como un valor más: dos con el mismo nombre y sin marca chocan.
 *
 * Es la única regla: la base de datos no tiene índice único de productos (ver la
 * migración drop_unique_index_from_productos_table). MySQL compara sin distinguir
 * mayúsculas ni tildes (utf8mb4_unicode_ci): "Deji" y "DEJI" son la misma marca.
 *
 * @mixin \Illuminate\Foundation\Http\FormRequest
 */
trait ValidaNombreUnicoProducto
{
    /**
     * @param  int|null  $ignorarId  El producto que se edita (null al crear), para que no choque consigo mismo.
     */
    protected function reglaNombreUnico(?int $ignorarId = null): \Closure
    {
        return function (string $_attribute, mixed $value, \Closure $fail) use ($ignorarId) {
            // Un nombre o una marca que no son texto ya los rechaza la regla 'string';
            // aquí solo se evita que armar el mensaje falle (daría un error 500)
            if (! is_string($value)) {
                return;
            }

            $marca = is_string($this->input('marca')) ? $this->input('marca') : null;
            $marco = $this->input('marco');

            $query = Producto::activos()
                ->where('categoria_id', $this->input('categoria_id'))
                ->where('nombre', $value)
                ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId));

            if ($this->input('tipo_producto_id')) {
                $query->where('tipo_producto_id', $this->input('tipo_producto_id'));
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

            if ($query->exists()) {
                $fail($this->mensajeNombreRepetido($value, $marca, $marco));
            }
        };
    }

    /**
     * Dice contra qué chocó, para que se sepa qué cambiar:
     * «Ya existe «Batería iPhone 14» de la marca «Deji».»
     * «Ya existe «Batería iPhone 14» sin marca. Si es de otra marca, escríbela en el campo Marca.»
     * En pantallas agrega si es con marco o sin marco.
     */
    private function mensajeNombreRepetido(string $nombre, ?string $marca, mixed $marco): string
    {
        $mensaje = "Ya existe «{$nombre}» " . (filled($marca) ? "de la marca «{$marca}»" : 'sin marca');

        if (! is_null($marco)) {
            $mensaje .= $marco ? ', con marco' : ', sin marco';
        }

        return $mensaje . (filled($marca) ? '.' : '. Si es de otra marca, escríbela en el campo Marca.');
    }
}
