<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCajaRequest extends FormRequest
{
    /**
     * Bag nombrado para que los errores de este formulario no se mezclen
     * con los del formulario de reparaciones en la misma página.
     * La vista usa $errors->getBag('caja') para mostrarlos en el modal correcto.
     */
    protected $errorBag = 'caja';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grupo' => [
                'required',
                'string',
                'max:1',
                'regex:/^[A-Za-z]$/',
            ],
            'numero' => [
                'required',
                'integer',
                'min:1',
                'max:999',
                // Unicidad compuesta: no puede existir otra caja con el mismo grupo+numero.
                // Se normaliza el grupo a mayúscula para que A1 y a1 sean la misma caja.
                Rule::unique('cajas', 'numero')->where(
                    fn ($q) => $q->where('grupo', strtoupper($this->input('grupo', '')))
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'grupo.required'  => 'El grupo es obligatorio.',
            'grupo.max'       => 'El grupo debe ser una sola letra.',
            'grupo.regex'     => 'El grupo debe ser una letra (A–Z).',
            'numero.required' => 'El número es obligatorio.',
            'numero.integer'  => 'El número debe ser un entero.',
            'numero.min'      => 'El número debe ser al menos 1.',
            'numero.unique'   => 'Ya existe una caja con ese grupo y número.',
        ];
    }
}
