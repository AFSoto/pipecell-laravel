<?php

namespace App\Http\Requests;

use App\Models\Caja;
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
            'caja_id' => [
                'required',
                // Verifica que el ID exista en la tabla cajas
                'exists:cajas,id',
                // Regla personalizada: verifica que la caja esté libre al momento de guardar.
                // Esto atrapa el caso donde el usuario ya tenía el formulario abierto
                // pero otra persona ocupó la caja antes de que él enviara el formulario.
                function (string $_attribute, mixed $value, \Closure $fail) {
                    // Buscamos la caja. findOrFail no aplica aquí porque
                    // la regla 'exists' anterior ya garantiza que existe;
                    // usamos find y verificamos null por si acaso.
                    $caja = Caja::find($value);

                    // Si por alguna razón no se encontró, dejamos que 'exists' maneje el error
                    if (! $caja) {
                        return;
                    }

                    // Si la caja tiene una reparación activa, rechazamos la solicitud
                    if (! $caja->estaLibre()) {
                        $fail('La caja seleccionada ya está ocupada. Selecciona otra.');
                    }
                },
            ],
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
