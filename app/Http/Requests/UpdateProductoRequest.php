<?php

namespace App\Http\Requests;

use App\Models\Categoria;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida los datos para actualizar un producto existente.
 *
 * La diferencia clave con StoreProductoRequest está en la regla de 'codigo':
 * se ignora el registro actual en la validación unique para que el producto
 * pueda guardarse con su propio código sin que falle por "ya existe".
 */
class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Obtenemos el ID del producto que viene en la ruta (route model binding)
        // para ignorarlo en la validación unique del código
        $productoId = $this->route('producto')->id;

        return [
            'categoria_id'      => ['required', 'exists:categorias,id'],
            'tipo_producto_id'  => ['nullable', 'exists:tipo_productos,id'],
            'codigo'            => ['nullable', 'string', 'max:50', "unique:productos,codigo,{$productoId}"],
            'nombre'            => ['required', 'string', 'max:150'],
            'descripcion'       => ['nullable', 'string'],
            'marco'             => ['nullable', 'boolean', $this->reglaMarco()],
            'precio_compra'     => ['required', 'numeric', 'min:0'],
            'precio_venta'      => ['required', 'numeric', 'min:0'],
            'stock'             => ['required', 'integer', 'min:0'],
            'stock_minimo'      => ['nullable', 'integer', 'min:0'],
            'imagenes'          => ['nullable', 'array'],
            'imagenes.*'        => ['image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.required'     => 'Debes seleccionar una categoría.',
            'categoria_id.exists'       => 'La categoría seleccionada no existe.',
            'tipo_producto_id.exists'   => 'El tipo de producto seleccionado no existe.',
            'codigo.unique'             => 'Este código ya está en uso por otro producto.',
            'nombre.required'           => 'El nombre del producto es obligatorio.',
            'precio_compra.required'    => 'El precio de compra es obligatorio.',
            'precio_compra.min'         => 'El precio de compra no puede ser negativo.',
            'precio_venta.required'     => 'El precio de venta es obligatorio.',
            'precio_venta.min'          => 'El precio de venta no puede ser negativo.',
            'stock.required'            => 'El stock es obligatorio.',
            'stock.min'                 => 'El stock no puede ser negativo.',
            'imagenes.*.image'          => 'Cada archivo debe ser una imagen válida.',
            'imagenes.*.max'            => 'Cada imagen no puede superar los 2 MB.',
        ];
    }

    private function reglaMarco(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $categoria = Categoria::find($this->categoria_id);
            if ($categoria && str_contains(strtolower($categoria->nombre), 'pantalla') && is_null($value)) {
                $fail('Debes indicar si la pantalla viene con marco o no.');
            }
        };
    }
}
