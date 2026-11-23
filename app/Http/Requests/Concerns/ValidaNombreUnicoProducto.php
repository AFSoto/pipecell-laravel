<?php

namespace App\Http\Requests\Concerns;

use App\Support\ProductoRepetido;

/**
 * Regla de "producto repetido" en el formulario, compartida por StoreProductoRequest y
 * UpdateProductoRequest: mismo nombre, categoría, tipo, marco y marca que otro activo.
 * La regla está en App\Support\ProductoRepetido, que ProductoService vuelve a revisar
 * justo antes de guardar.
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
            // Un nombre que no es texto ya lo rechaza la regla 'string'; aquí solo se evita
            // que armar el mensaje falle (daría un error 500)
            if (! is_string($value)) {
                return;
            }

            $mensaje = ProductoRepetido::buscar(
                ['nombre' => $value] + $this->only(['categoria_id', 'tipo_producto_id', 'marco', 'marca']),
                $ignorarId
            );

            if ($mensaje !== null) {
                $fail($mensaje);
            }
        };
    }
}
