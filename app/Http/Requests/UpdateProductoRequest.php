<?php

namespace App\Http\Requests;

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
            'categoria_id'  => ['required', 'exists:categorias,id'],

            // unique:tabla,columna,id_a_ignorar — ignora el propio registro
            'codigo'        => ['nullable', 'string', 'max:50', "unique:productos,codigo,{$productoId}"],

            'nombre'        => ['required', 'string', 'max:150'],
            'descripcion'   => ['nullable', 'string'],
            'precio_compra' => ['required', 'numeric', 'min:0'],
            'precio_venta'  => ['required', 'numeric', 'min:0'],
            'stock'         => ['required', 'integer', 'min:0'],
            'stock_minimo'  => ['nullable', 'integer', 'min:0'],

            // Imágenes nuevas a agregar (opcionales en la edición)
            'imagenes'      => ['nullable', 'array'],
            'imagenes.*'    => ['image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.required'  => 'Debes seleccionar una categoría.',
            'categoria_id.exists'    => 'La categoría seleccionada no existe.',
            'codigo.unique'          => 'Este código ya está en uso por otro producto.',
            'nombre.required'        => 'El nombre del producto es obligatorio.',
            'precio_compra.required' => 'El precio de compra es obligatorio.',
            'precio_compra.min'      => 'El precio de compra no puede ser negativo.',
            'precio_venta.required'  => 'El precio de venta es obligatorio.',
            'precio_venta.min'       => 'El precio de venta no puede ser negativo.',
            'stock.required'         => 'El stock es obligatorio.',
            'stock.min'              => 'El stock no puede ser negativo.',
            'imagenes.*.image'       => 'Cada archivo debe ser una imagen válida.',
            'imagenes.*.max'         => 'Cada imagen no puede superar los 2 MB.',
        ];
    }
}
