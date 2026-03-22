<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de clientes del local.
     *
     * Separada de reparaciones para no repetir datos.
     * Si un cliente viene 3 veces, su nombre y teléfono
     * se guardan una sola vez aquí y se referencian con cliente_id.
     */
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            // Datos obligatorios — lo mínimo para registrar un cliente rápido
            $table->string('nombre', 100);
            $table->string('telefono', 20);

            // Datos opcionales — se pueden llenar después si se necesitan
            $table->string('cedula', 20)->nullable()->unique();
            $table->string('email', 150)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
