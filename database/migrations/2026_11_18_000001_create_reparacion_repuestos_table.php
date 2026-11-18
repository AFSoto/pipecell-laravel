<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repuestos del inventario usados en una reparación.
 *
 * Por ahora la interfaz solo maneja un display por reparación, pero la tabla
 * admite varios repuestos para poder ampliarlo sin migrar datos.
 * El stock se descuenta con ProductoService::ajustarStock(), que deja el
 * MovimientoStock correspondiente en el historial del inventario.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reparacion_repuestos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reparacion_id')->constrained('reparaciones')->cascadeOnDelete();

            // Sin cascade: los productos nunca se borran físicamente (se desactivan)
            $table->foreignId('producto_id')->constrained('productos');

            // Unidades usadas — normalmente 1
            $table->unsignedSmallInteger('cantidad')->default(1);

            // Copia del precio_compra al momento de usarlo, para que el costo
            // histórico no cambie si después se actualiza el precio del producto
            $table->decimal('costo_unitario', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reparacion_repuestos');
    }
};
