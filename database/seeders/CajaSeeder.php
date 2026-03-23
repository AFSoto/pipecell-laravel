<?php

namespace Database\Seeders;

use App\Models\Caja;
use Illuminate\Database\Seeder;

/**
 * Seeder para crear las cajas iniciales del local.
 *
 * Crea 3 grupos (A, B, C) con 3 cajas cada uno.
 * Resultado: A1, A2, A3, B1, B2, B3, C1, C2, C3
 *
 * Si necesitas más cajas o grupos, solo editas el array $grupos.
 * Ejemplo: 'D' => 5 crea D1, D2, D3, D4, D5
 */
class CajaSeeder extends Seeder
{
    public function run(): void
    {
        // Cada key es el grupo (letra) y el valor es cuántas cajas crear
        $grupos = [
            'A' => 5,
            'B' => 5,
            'C' => 5,
        ];

        // Recorre cada grupo y crea sus cajas
        foreach ($grupos as $grupo => $cantidad) {
            for ($i = 1; $i <= $cantidad; $i++) {
                Caja::create([
                    'grupo' => $grupo,
                    'numero' => $i,
                    // estado no se pone porque el default es 'libre'
                ]);
            }
        }
    }
}
