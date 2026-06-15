<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

/**
 * Seeder de categorías iniciales para PipeCell.
 *
 * Carga las 8 familias de productos que maneja el negocio.
 * Está pensado para ejecutarse una sola vez en un ambiente limpio;
 * si se vuelve a correr, firstOrCreate evita duplicados.
 *
 * Se ejecuta con: php artisan db:seed --class=CategoriaSeeder
 * O junto con los demás: php artisan migrate:fresh --seed
 */
class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        // Cada categoría tiene nombre y descripción que orientan al operario
        // sobre qué tipo de productos incluir en ella.
        $categorias = [
            [
                'nombre'      => 'Accesorios de Carga',
                'descripcion' => 'Cables USB, adaptadores, cargadores de pared y bases de carga inalámbrica.',
            ],
            [
                'nombre'      => 'Audio y Sonido',
                'descripcion' => 'Audífonos Bluetooth, diademas con cable, parlantes portátiles y manos libres.',
            ],
            [
                'nombre'      => 'Protección y Estética',
                'descripcion' => 'Estuches, vidrios templados, micas de hidrogel y protectores de cámara.',
            ],
            [
                'nombre'      => 'Repuestos - Pantallas',
                'descripcion' => 'Displays LCD, OLED y AMOLED; módulos táctiles para reparación de equipos.',
            ],
            [
                'nombre'      => 'Repuestos - Baterías',
                'descripcion' => 'Baterías internas de reemplazo y bancos de carga externos.',
            ],
            [
                'nombre'      => 'Componentes Internos',
                'descripcion' => 'Pines de carga, flex de botones, módulos de cámara, micrófonos y altavoces internos.',
            ],
            [
                'nombre'      => 'Almacenamiento',
                'descripcion' => 'Tarjetas de memoria Micro SD y memorias USB de distintas capacidades.',
            ],
            [
                'nombre'      => 'Herramientas Técnicas',
                'descripcion' => 'Estaño para soldadura, flux desoxidante, destornilladores y herramientas de apertura.',
            ],
        ];

        foreach ($categorias as $datos) {
            // firstOrCreate evita duplicar si el seeder se corre más de una vez
            Categoria::firstOrCreate(
                ['nombre' => $datos['nombre']],   // condición de búsqueda
                ['descripcion' => $datos['descripcion'], 'estado' => 'activo']
            );
        }
    }
}
