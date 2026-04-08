<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder principal que orquesta todos los demás.
 *
 * Cuando ejecutas "php artisan db:seed", Laravel llama a este archivo
 * y este se encarga de llamar a los demás seeders en orden.
 *
 * El orden importa: primero Users (porque reparaciones depende de users),
 * luego Cajas (porque reparaciones depende de cajas).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CajaSeeder::class,
            CategoriaSeeder::class, // Categorías del inventario de productos
        ]);
    }
}
