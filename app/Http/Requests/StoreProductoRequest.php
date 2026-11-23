<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaNombreUnicoProducto;
use App\Models\Categoria;
use App\Support\FotoProducto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida los datos para crear un nuevo producto.
 *
 * Fotos: hasta 10, cada una una imagen de hasta 20 MB y 50 megapíxeles. Se guardan
 * convertidas a WebP liviano, así que el peso original no importa (reglas y mensajes en
 * FotoProducto). Repetido = mismo nombre, categoría, tipo, marco y marca (ver
 * ValidaNombreUnicoProducto).
 */
class StoreProductoRequest extends FormRequest
{
    use ValidaNombreUnicoProducto;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'categoria_id'      => ['required', 'exists:categorias,id'],
            'tipo_producto_id'  => ['nullable', 'exists:tipo_productos,id'],
            'codigo'            => ['nullable', 'string', 'max:50', 'unique:productos,codigo'],
            'nombre'            => ['required', 'string', 'max:150', $this->reglaNombreUnico()],
            'descripcion'       => ['nullable', 'string'],
            'marco'             => ['nullable', 'boolean', $this->reglMarco()],
            'marca'             => ['nullable', 'string', 'max:100', $this->reglaExclusivaPantalla('marca', 'La marca es obligatoria para pantallas.')],
            'referencia'        => ['nullable', 'string', 'max:100', $this->reglaExclusivaPantalla('referencia', 'La referencia es obligatoria para pantallas.')],

            // Opcional: vacío se guarda como NULL ("sin precio de compra"), que no es lo mismo que 0
            'precio_compra'     => ['nullable', 'numeric', 'min:0'],
            'precio_venta'      => ['required', 'numeric', 'min:0'],
            'stock'             => ['required', 'integer', 'min:0'],
            'stock_minimo'      => ['nullable', 'integer', 'min:0'],
            ...FotoProducto::reglas(),
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.required'     => 'Debes seleccionar una categoría.',
            'categoria_id.exists'       => 'La categoría seleccionada no existe.',
            'tipo_producto_id.exists'   => 'El tipo de producto seleccionado no existe.',
            'codigo.unique'             => 'Este código ya está en uso por otro producto.',
            'codigo.max'                => 'El código no puede superar los 50 caracteres.',
            'nombre.required'           => 'El nombre del producto es obligatorio.',
            'nombre.max'                => 'El nombre no puede superar los 150 caracteres.',
            'precio_compra.numeric'     => 'El precio de compra debe ser un número.',
            'precio_compra.min'         => 'El precio de compra no puede ser negativo.',
            'precio_venta.required'     => 'El precio de venta es obligatorio.',
            'precio_venta.numeric'      => 'El precio de venta debe ser un número.',
            'precio_venta.min'          => 'El precio de venta no puede ser negativo.',
            'stock.required'            => 'El stock es obligatorio.',
            'stock.integer'             => 'El stock debe ser un número entero.',
            'stock.min'                 => 'El stock no puede ser negativo.',
            ...FotoProducto::mensajes(),
        ];
    }

    private function reglMarco(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $categoria = Categoria::find($this->categoria_id);
            if ($categoria?->esDePantallas() && is_null($value)) {
                $fail('Debes indicar si la pantalla viene con marco o no.');
            }
        };
    }

    private function reglaExclusivaPantalla(string $campo, string $mensaje): \Closure
    {
        return function ($attribute, $value, $fail) use ($mensaje) {
            $categoria = Categoria::find($this->categoria_id);
            if ($categoria?->esDePantallas() && empty($value)) {
                $fail($mensaje);
            }
        };
    }
}
