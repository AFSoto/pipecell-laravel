<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de cajas físicas del local.
     *
     * Cada caja se identifica por grupo + número (ej: A1, B3, C2).
     * El grupo es la letra (A, B, C...) y el número es la posición
     * dentro del grupo. El unique compuesto evita duplicados como
     * dos cajas "A1".
     */
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();

            // Grupo: letra que identifica la sección (A, B, C, D...)
            $table->string('grupo', 1);

            // Número: posición dentro del grupo (1, 2, 3...)
            // unsignedInteger porque no puede ser negativo
            $table->unsignedInteger('numero');

            // Descripción opcional: ubicación física ("Estante superior", etc.)
            $table->string('descripcion', 100)->nullable();

            // Estado: 'libre' u 'ocupada' — se castea al Enum EstadoCaja en el modelo
            $table->string('estado', 20)->default('libre');

            $table->timestamps();

            // Índice único compuesto: no puede haber dos cajas A1, A2, etc.
            // Esto es como decirle a la BD: la combinación grupo+numero no se repite
            $table->unique(['grupo', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};
