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
            // Los usa ReparacionService al descontar o devolver el display de una reparación
            // (también se crean solos con firstOrCreate si no existen)
            'Uso en reparación',
            'Devolución de reparación',
        ];

        foreach ($tipos as $nombre) {
            TipoMovimientoStock::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
