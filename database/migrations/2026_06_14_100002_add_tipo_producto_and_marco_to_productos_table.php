<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Tipo de producto (genérico, original, semi-original, etc.) — opcional
            $table->foreignId('tipo_producto_id')
                ->nullable()
                ->after('categoria_id')
                ->constrained('tipo_productos')
                ->nullOnDelete();

            // Si la pantalla viene con marco integrado — solo aplica a categoría Pantallas
            $table->boolean('marco')->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['tipo_producto_id']);
            $table->dropColumn(['tipo_producto_id', 'marco']);
        });
    }
};
