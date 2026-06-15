<?php

namespace Database\Seeders;

use App\Models\TipoProducto;
use Illuminate\Database\Seeder;

class TipoProductoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            'Genérico',
            'Original',
            'Semi-original',
            'Compatible',
            'Reacondicionado',
        ];

        foreach ($tipos as $nombre) {
            TipoProducto::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
