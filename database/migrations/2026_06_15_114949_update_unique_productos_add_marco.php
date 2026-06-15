<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('productos_categoria_tipo_nombre_unique');
            $table->unique(['categoria_id', 'tipo_producto_id', 'marco', 'nombre'], 'productos_unique');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('productos_unique');
            $table->unique(['categoria_id', 'tipo_producto_id', 'nombre'], 'productos_categoria_tipo_nombre_unique');
        });
    }
};
