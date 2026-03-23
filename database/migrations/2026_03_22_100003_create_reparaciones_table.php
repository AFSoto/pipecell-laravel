<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla principal de reparaciones.
     *
     * Cada registro es un celular que entró al local para ser reparado.
     * Se relaciona con: cliente (quién lo trajo), caja (dónde se guardó),
     * y opcionalmente con el técnico que lo reparó.
     *
     * foreignId() crea la columna + la foreign key en una sola línea.
     * constrained() le dice a Laravel a qué tabla apunta.
     */
    public function up(): void
    {
        Schema::create('reparaciones', function (Blueprint $table) {
            $table->id();

            // ── Foreign Keys (relaciones con otras tablas) ──

            // Cliente que trajo el celular — obligatorio
            $table->foreignId('cliente_id')->constrained('clientes');

            // Técnico que hizo la reparación — nullable para implementar después
            $table->foreignId('tecnico_id')->nullable()->constrained('users');

            // Caja donde se guardó el celular — obligatorio
            $table->foreignId('caja_id')->constrained('cajas');

            // ── Datos del celular ──

            // Marca siempre se llena (Samsung, iPhone, Xiaomi...)
            $table->string('marca', 50);

            // Modelo es opcional (a veces el técnico no lo sabe de inmediato)
            $table->string('modelo', 50)->nullable();

            // Qué le pasa al celular — opcional pero recomendado
            $table->text('descripcion_falla')->nullable();

            // ── Valores monetarios ──

            // Precio total que se le cobra al cliente
            // decimal(10,2) = hasta 99,999,999.99 — suficiente para pesos colombianos
            $table->decimal('valor_total', 10, 2);

            // Cuánto costaron los repuestos — nullable, se llena si se quiere
            $table->decimal('costo_repuestos', 10, 2)->nullable();

            // ── Estado y fechas ──

            // Estado: 'en_proceso', 'arreglado', 'entregado'
            // Se castea al Enum EstadoReparacion en el modelo
            $table->string('estado', 20)->default('en_proceso');

            // Fecha real de entrega — se llena cuando el estado pasa a 'entregado'
            $table->dateTime('fecha_entrega')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reparaciones');
    }
};
