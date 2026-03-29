<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla principal de reparaciones.
     *
     * Simplificada: nombre y teléfono del cliente van directo aquí,
     * sin tabla de clientes separada. Más rápido para el técnico.
     */
    public function up(): void
    {
        Schema::create('reparaciones', function (Blueprint $table) {
            $table->id();

            // ── Datos del cliente (directo, sin FK) ──
            $table->string('nombre_cliente', 100);
            $table->string('telefono_cliente', 20)->nullable();

            // ── Foreign Keys ──
            $table->foreignId('tecnico_id')->nullable()->constrained('users');
            $table->foreignId('caja_id')->constrained('cajas');

            // ── Datos del celular ──
            $table->string('marca', 50);
            $table->string('modelo', 50)->nullable();
            $table->text('descripcion_falla')->nullable();

            // ── Valores monetarios ──
            $table->decimal('valor_total', 10, 2);
            $table->decimal('costo_repuestos', 10, 2)->nullable();

            // ── Estado y fechas ──
            $table->string('estado', 20)->default('en_proceso');
            $table->dateTime('fecha_entrega')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reparaciones');
    }
};
