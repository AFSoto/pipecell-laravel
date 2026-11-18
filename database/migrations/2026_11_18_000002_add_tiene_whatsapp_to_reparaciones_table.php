<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indica si el teléfono del cliente tiene WhatsApp.
 *
 * Si es false, no se le envía el aviso de "reparación lista".
 * Default true: la gran mayoría de celulares en Colombia tienen WhatsApp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reparaciones', function (Blueprint $table) {
            $table->boolean('tiene_whatsapp')->default(true)->after('telefono_cliente');
        });
    }

    public function down(): void
    {
        Schema::table('reparaciones', function (Blueprint $table) {
            $table->dropColumn('tiene_whatsapp');
        });
    }
};
