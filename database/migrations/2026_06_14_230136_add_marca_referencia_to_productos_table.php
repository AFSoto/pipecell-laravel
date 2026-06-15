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
            // Campos exclusivos de pantallas (Xiaomi, Motorola… / G20, 15C…)
            $table->string('marca', 100)->nullable()->after('marco');
            $table->string('referencia', 100)->nullable()->after('marca');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['marca', 'referencia']);
        });
    }
};
