<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida los datos para crear un nuevo producto.
 *
 * Incluye validación de imágenes: cada archivo debe ser una imagen
 * y no superar los 2 MB (2048 KB).
 */
class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Categoría — debe existir en la BD para mantener integridad referencial
            'categoria_id'  => ['required', 'exists:categorias,id'],

            // Código único de barras o SKU — opcional
            'codigo'        => ['nullable', 'string', 'max:50', 'unique:productos,codigo'],

            // Datos básicos del producto
            'nombre'        => ['required', 'string', 'max:150'],
            'descripcion'   => ['nullable', 'string'],

            // Precios — no pueden ser negativos
            'precio_compra' => ['required', 'numeric', 'min:0'],
            'precio_venta'  => ['required', 'numeric', 'min:0'],

            // Control de inventario
            'stock'         => ['required', 'integer', 'min:0'],
            'stock_minimo'  => ['nullable', 'integer', 'min:0'],

            // Imágenes — el array en sí es opcional, pero si viene, cada elemento debe ser imagen
            'imagenes'      => ['nullable', 'array'],
            'imagenes.*'    => ['image', 'max:2048'], // max 2 MB por imagen
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.required'  => 'Debes seleccionar una categoría.',
            'categoria_id.exists'    => 'La categoría seleccionada no existe.',
            'codigo.unique'          => 'Este código ya está en uso por otro producto.',
            'codigo.max'             => 'El código no puede superar los 50 caracteres.',
            'nombre.required'        => 'El nombre del producto es obligatorio.',
            'nombre.max'             => 'El nombre no puede superar los 150 caracteres.',
            'precio_compra.required' => 'El precio de compra es obligatorio.',
            'precio_compra.numeric'  => 'El precio de compra debe ser un número.',
            'precio_compra.min'      => 'El precio de compra no puede ser negativo.',
            'precio_venta.required'  => 'El precio de venta es obligatorio.',
            'precio_venta.numeric'   => 'El precio de venta debe ser un número.',
            'precio_venta.min'       => 'El precio de venta no puede ser negativo.',
            'stock.required'         => 'El stock es obligatorio.',
            'stock.integer'          => 'El stock debe ser un número entero.',
            'stock.min'              => 'El stock no puede ser negativo.',
            'imagenes.*.image'       => 'Cada archivo debe ser una imagen válida (JPG, PNG, etc.).',
            'imagenes.*.max'         => 'Cada imagen no puede superar los 2 MB.',
        ];
    }
}
