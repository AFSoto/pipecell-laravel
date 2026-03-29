<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reparaciones', function (Blueprint $table) {
            // Índice compuesto para optimizar las consultas más frecuentes:
            // filtro por estado + ordenar por fecha (latest()).
            $table->index(['estado', 'created_at'], 'idx_reparaciones_estado_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('reparaciones', function (Blueprint $table) {
            $table->dropIndex('idx_reparaciones_estado_created_at');
        });
    }
};
