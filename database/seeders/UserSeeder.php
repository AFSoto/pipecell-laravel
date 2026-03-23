<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder para crear el usuario administrador inicial.
 *
 * Los seeders son clases que llenan la BD con datos iniciales.
 * Este crea el admin que va a poder loguearse al sistema.
 *
 * Se ejecuta con: php artisan db:seed
 * O junto con las migraciones: php artisan migrate:fresh --seed
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // create() guarda directamente en la BD.
        // El password se hashea automáticamente gracias al cast 'hashed'
        // que pusimos en el modelo User.
        User::create([
            'nombre' => 'Administrador',
            'email' => 'admin@pipecell.com',
            'password' => 'password', // Cámbialo después en producción
            'role' => 'admin',
            'estado' => 'activo',
        ]);
    }
}
