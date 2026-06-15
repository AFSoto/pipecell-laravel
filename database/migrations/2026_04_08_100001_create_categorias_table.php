<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de categorías para agrupar los productos del inventario.
     *
     * Ejemplos de categorías: "Accesorios de Carga", "Repuestos - Pantallas".
     * Se usa estado ('activo' / 'inactivo') en lugar de soft deletes
     * para mantener coherencia con el resto del proyecto.
     */
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();

            // Nombre visible de la categoría, máx 100 caracteres
            $table->string('nombre', 100);

            // Descripción opcional para dar más contexto a la categoría
            $table->text('descripcion')->nullable();

            // Estado lógico: 'activo' o 'inactivo'
            // Se usa en lugar de softDeletes para evitar complicar las consultas
            $table->string('estado', 20)->default('activo');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
