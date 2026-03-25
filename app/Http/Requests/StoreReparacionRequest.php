<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida los datos al crear una nueva reparación.
 *
 * Versión simplificada: nombre y teléfono directo,
 * sin buscar en tabla de clientes.
 */
class StoreReparacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Cliente (directo) ──
            'nombre_cliente'    => ['required', 'string', 'max:100'],
            'telefono_cliente'  => ['nullable', 'string', 'max:20'],

            // ── Reparación ──
            'caja_id'           => ['required', 'exists:cajas,id'],
            'marca'             => ['required', 'string', 'max:50'],
            'modelo'            => ['nullable', 'string', 'max:50'],
            'descripcion_falla' => ['nullable', 'string'],
            'valor_total'       => ['required', 'numeric', 'min:0'],
            'costo_repuestos'   => ['nullable', 'numeric', 'min:0'],

            // ── Abono inicial ──
            'abono_inicial'     => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_cliente.required' => 'El nombre del cliente es obligatorio.',
            'caja_id.required'        => 'Debes seleccionar una caja.',
            'caja_id.exists'          => 'La caja seleccionada no existe.',
            'marca.required'          => 'La marca del celular es obligatoria.',
            'valor_total.required'    => 'El valor total es obligatorio.',
            'valor_total.numeric'     => 'El valor total debe ser un número.',
            'valor_total.min'         => 'El valor total no puede ser negativo.',
        ];
    }
}
