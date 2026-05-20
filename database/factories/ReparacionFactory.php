<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Caja;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\EstadoReparacion;
/**
 * @extends Factory<\App\Models\Reparacion>
 */
class ReparacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'nombre_cliente' => fake()->name(),

            'telefono_cliente' => fake()->numerify('3#########'),

            'tecnico_id' => User::inRandomOrder()->value('id'),

            'caja_id' => Caja::inRandomOrder()->value('id'),

            'marca' => fake()->randomElement([
                'Samsung',
                'iPhone',
                'Xiaomi',
                'Motorola',
                'Huawei',
                'Oppo',
                'Realme',
            ]),

            'modelo' => strtoupper(
                fake()->bothify('??-###')
            ),

            'descripcion_falla' => fake()->randomElement([
                'No enciende',
                'Pantalla rota',
                'No carga',
                'Se reinicia solo',
                'Cambio de batería',
                'Problema de software',
                'Daño por agua',
                'Micrófono no funciona',
                'Cámara dañada',
            ]),

            'valor_total' => fake()->numberBetween(
                80000,
                800000
            ),

            'costo_repuestos' => fake()->numberBetween(
                20000,
                300000
            ),

            'estado' => fake()->randomElement([
                'arreglado',
                'entregado',
            ]),
        ];
    }
}
