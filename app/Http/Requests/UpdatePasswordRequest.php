<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    /**
     * Bag separado del formulario de información personal.
     */
    protected $errorBag = 'contrasena';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 'current_password' verifica contra el hash almacenado del usuario autenticado
            'password_actual'             => ['required', 'current_password'],
            'nueva_password'              => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'nueva_password_confirmation' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'password_actual.required'         => 'Debes ingresar tu contraseña actual.',
            'password_actual.current_password'  => 'La contraseña actual no es correcta.',
            'nueva_password.required'           => 'La nueva contraseña es obligatoria.',
            'nueva_password.confirmed'          => 'La confirmación no coincide con la nueva contraseña.',
            'nueva_password.password'           => 'La contraseña debe tener al menos 8 caracteres, mayúsculas, minúsculas, números y un carácter especial.',
            'nueva_password_confirmation.required' => 'Debes confirmar la nueva contraseña.',
        ];
    }
}
