<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerfilRequest extends FormRequest
{
    /**
     * Bag nombrado para que los errores de este formulario no se mezclen
     * con los del formulario de contraseña en la misma página.
     */
    protected $errorBag = 'perfil';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'email'  => [
                'required',
                'email',
                'max:150',
                // Unicidad ignorando el propio usuario para no fallar al guardar sin cambiar el email
                Rule::unique('users', 'email')->ignore(auth()->id()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max'      => 'El nombre no puede superar los 100 caracteres.',
            'email.required'  => 'El correo electrónico es obligatorio.',
            'email.email'     => 'Ingresa un correo electrónico válido.',
            'email.max'       => 'El correo no puede superar los 150 caracteres.',
            'email.unique'    => 'Este correo ya está en uso por otra cuenta.',
        ];
    }
}
