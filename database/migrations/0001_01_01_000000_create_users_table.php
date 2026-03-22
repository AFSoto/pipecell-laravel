<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de usuarios del sistema (admins y técnicos).
     *
     * Reemplaza las tablas "usuarios" y "tipos_usuario" del proyecto anterior.
     * El rol ahora es un string que se castea al Enum RolUsuario en el modelo.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // ID autoincremental (Laravel usa bigint por defecto con id())
            $table->id();

            // Datos básicos del usuario
            $table->string('nombre', 100);
            $table->string('email', 150)->unique();
            $table->string('password'); // Laravel lo hashea automáticamente con el cast

            // Rol: 'admin' o 'tecnico' — se castea al Enum RolUsuario en el modelo
            $table->string('role', 20)->default('tecnico');

            // Estado de la cuenta: 'activo' o 'inactivo' — para desactivar sin borrar
            $table->string('estado', 20)->default('activo');

            // Fecha del último inicio de sesión — nullable porque al crear no ha entrado
            $table->dateTime('last_login_at')->nullable();

            // created_at y updated_at — Laravel los llena automáticamente
            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla si hacemos rollback.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
