<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de cabecera de ventas (equivalente a una factura).
     *
     * Cada fila representa una transacción completa de venta.
     * Los productos vendidos se guardan en venta_items (patrón maestro-detalle).
     *
     * Se guarda el total calculado para evitar recalcularlo en cada consulta
     * y para que quede registrado aunque el precio del producto cambie después.
     */
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();

            // FK al usuario que registró la venta en el sistema
            $table->foreignId('user_id')->constrained('users');

            // Total de la venta — suma de todos los subtotales de venta_items
            // Se guarda explícitamente para rendimiento y auditoría
            $table->decimal('total', 10, 2);

            // Nota libre opcional: "Cliente pidió garantía", "Venta a crédito", etc.
            $table->string('nota', 255)->nullable();

            $table->timestamps();

            // Índice en created_at para filtros por fecha (ventas de hoy, del mes, etc.)
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
