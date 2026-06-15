<?php

namespace Database\Seeders;

use App\Models\TipoMovimientoStock;
use Illuminate\Database\Seeder;

class TipoMovimientoStockSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            'Compra',
            'Venta',
            'Devolución',
            'Ajuste de inventario',
            'Pérdida / daño',
            'Inventario inicial',
        ];

        foreach ($tipos as $nombre) {
            TipoMovimientoStock::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
