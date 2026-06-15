<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory encargada de generar datos falsos
 * para el modelo Abono.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Abono>
 */
class AbonoFactory extends Factory
{
    /**
     * Define el estado por defecto del modelo.
     */
    public function definition(): array
    {
        return [

            // Valor del abono.
            // Se inicializa en 0 porque normalmente
            // será calculado o asignado manualmente.
            'monto' => 0,

            // Genera una nota aleatoria relacionada al pago
            'nota' => fake()->randomElement([
                'Primer abono',
                'Pago parcial',
                'Abono cliente',
                'Pago final',
            ]),
        ];
    }
}
