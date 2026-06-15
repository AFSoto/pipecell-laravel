<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida los datos para crear una nueva categoría.
 *
 * Separar la validación del controller sigue el principio de
 * responsabilidad única: el controller solo orquesta, el Request valida.
 */
class StoreCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Por ahora cualquier usuario autenticado puede crear categorías.
        // Si se agrega control de roles, aquí se verificaría.
        return true;
    }

    public function rules(): array
    {
        return [
            // Nombre visible en menús y listados — obligatorio y acotado
            'nombre'      => ['required', 'string', 'max:100'],
            // Descripción informativa — opcional
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
