<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida los datos para actualizar una categoría existente.
 *
 * Las reglas son idénticas a StoreCategoriaRequest.
 * Se mantiene como clase separada para poder diferenciarlos en el futuro
 * (e.g., si se agrega unique ignorando el registro actual).
 */
class UpdateCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'      => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.max'      => 'El nombre no puede superar los 100 caracteres.',
        ];
    }
}
