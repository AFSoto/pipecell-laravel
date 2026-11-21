<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Confirmación de entrega de los avisos: el webhook de Meta avisa cuándo el
 * mensaje llegó al celular del cliente y cuándo lo leyó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->timestamp('entregado_at')->nullable()->after('mensaje_id');
            $table->timestamp('leido_at')->nullable()->after('entregado_at');
        });
    }

    public function down(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropColumn(['entregado_at', 'leido_at']);
        });
    }
};
