<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El precio de compra pasa a ser opcional.
 *
 * Vacío (NULL) es "sin precio de compra": no se sabe cuánto costó. No es lo mismo
 * que 0, que dice que costó $0.
 *
 * reparacion_repuestos.costo_unitario es la copia del precio de compra al usar un
 * display, así que también acepta vacío: un producto sin precio se copia sin precio,
 * no como un costo de $0.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('precio_compra', 10, 2)->nullable()->change();
        });

        Schema::table('reparacion_repuestos', function (Blueprint $table) {
            $table->decimal('costo_unitario', 10, 2)->nullable()->change();
        });
    }

    /**
     * Las dos columnas vuelven a ser obligatorias; para eso, los vacíos quedan en 0.
     */
    public function down(): void
    {
        DB::table('productos')->whereNull('precio_compra')->update(['precio_compra' => 0]);
        DB::table('reparacion_repuestos')->whereNull('costo_unitario')->update(['costo_unitario' => 0]);

        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('precio_compra', 10, 2)->nullable(false)->change();
        });

        Schema::table('reparacion_repuestos', function (Blueprint $table) {
            $table->decimal('costo_unitario', 10, 2)->nullable(false)->default(0)->change();
        });
    }
};
