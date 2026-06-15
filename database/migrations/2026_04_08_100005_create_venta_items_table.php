<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de detalle de ventas — los productos de cada venta.
     *
     * Patrón maestro-detalle: ventas (cabecera) → venta_items (líneas).
     * Se guarda precio_unitario al momento de la venta porque el precio
     * del producto puede cambiar después y hay que mantener el histórico fiel.
     *
     * cascadeOnDelete en venta_id: si se borra una venta (poco frecuente),
     * sus ítems se borran también automáticamente.
     */
    public function up(): void
    {
        Schema::create('venta_items', function (Blueprint $table) {
            $table->id();

            // FK a la venta a la que pertenece esta línea
            $table->foreignId('venta_id')
                ->constrained('ventas')
                ->cascadeOnDelete();

            // FK al producto vendido — sin cascade para mantener historial
            // aunque el producto se desactive
            $table->foreignId('producto_id')->constrained('productos');

            // Cantidad de unidades vendidas en esta línea
            $table->unsignedInteger('cantidad');

            // Precio unitario al momento de la venta
            // IMPORTANTE: no se lee del producto porque puede cambiar luego
            $table->decimal('precio_unitario', 10, 2);

            // Subtotal = cantidad × precio_unitario
            // Se guarda para evitar recalcular y por integridad del historial
            $table->decimal('subtotal', 10, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_items');
    }
};
