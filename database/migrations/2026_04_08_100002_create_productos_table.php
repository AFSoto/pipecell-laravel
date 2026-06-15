<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla principal del inventario de productos.
     *
     * Guarda cada artículo que PipeCell vende o usa en reparaciones:
     * accesorios, repuestos, herramientas, etc.
     *
     * Se mantienen precio_compra y precio_venta separados para poder
     * calcular la ganancia unitaria y hacer reportes de rentabilidad.
     */
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();

            // FK a la categoría a la que pertenece el producto
            $table->foreignId('categoria_id')->constrained('categorias');

            // Código de barras o SKU interno — opcional y único
            // Útil para escaneo rápido o importación de catálogos
            $table->string('codigo', 50)->nullable()->unique();

            // Nombre comercial del producto, máx 150 caracteres
            $table->string('nombre', 150);

            // Descripción detallada: especificaciones, compatibilidad, etc.
            $table->text('descripcion')->nullable();

            // ── Precios ──
            // precio_compra: lo que pagó el negocio al proveedor
            $table->decimal('precio_compra', 10, 2);
            // precio_venta: lo que paga el cliente final
            $table->decimal('precio_venta', 10, 2);

            // ── Control de stock ──
            // stock: unidades físicamente disponibles
            $table->unsignedInteger('stock')->default(0);
            // stock_minimo: umbral para mostrar alerta de reposición
            $table->unsignedInteger('stock_minimo')->default(3);

            // Estado lógico del producto: 'activo' o 'inactivo'
            $table->string('estado', 20)->default('activo');

            $table->timestamps();

            // Índice en categoria_id porque es el filtro más frecuente
            // (listar productos de una categoría específica)
            $table->index('categoria_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
