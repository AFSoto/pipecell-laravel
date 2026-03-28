<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida los datos al editar una reparación existente.
 *
 * Solo valida los campos editables. Los campos que NO se editan aquí
 * (caja_id, estado, abonos) tienen sus propios flujos:
 *   - caja_id  → cambiarla implicaría liberar/ocupar cajas (otra funcionalidad)
 *   - estado   → se cambia con el select AJAX en el listado
 *   - abonos   → tienen su propio modal y endpoint
 */
class UpdateReparacionRequest extends FormRequest
{
    /**
     * Todos los usuarios autenticados pueden editar reparaciones.
     * El middleware 'auth' en las rutas ya protege el acceso.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Datos del cliente ──
            'nombre_cliente'   => ['required', 'string', 'max:100'],
            'telefono_cliente' => ['nullable', 'string', 'max:20'],

            // ── Datos del equipo ──
            'marca'            => ['required', 'string', 'max:50'],
            'modelo'           => ['nullable', 'string', 'max:50'],
            'descripcion_falla'=> ['nullable', 'string'],

            // ── Valores económicos ──
            // min:0 porque una reparación puede ser gratuita (garantía, favor)
            'valor_total'      => ['required', 'numeric', 'min:0'],
            'costo_repuestos'  => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_cliente.required'  => 'El nombre del cliente es obligatorio.',
            'nombre_cliente.max'       => 'El nombre no puede tener más de 100 caracteres.',
            'marca.required'           => 'La marca del celular es obligatoria.',
            'marca.max'                => 'La marca no puede tener más de 50 caracteres.',
            'valor_total.required'     => 'El valor total es obligatorio.',
            'valor_total.numeric'      => 'El valor total debe ser un número.',
            'valor_total.min'          => 'El valor total no puede ser negativo.',
            'costo_repuestos.numeric'  => 'El costo de repuestos debe ser un número.',
            'costo_repuestos.min'      => 'El costo de repuestos no puede ser negativo.',
        ];
    }
}
