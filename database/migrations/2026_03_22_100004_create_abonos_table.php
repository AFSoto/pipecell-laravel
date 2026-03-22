<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de abonos (pagos parciales) por reparación.
     *
     * Reemplaza el campo "abono" que antes estaba en reparaciones.
     * Ahora cada pago es un registro independiente con su fecha.
     *
     * El total abonado se calcula en el modelo Reparacion con:
     * $reparacion->abonos()->sum('monto')
     *
     * cascadeOnDelete() significa que si se borra una reparación,
     * sus abonos se borran automáticamente (no tiene sentido
     * un abono sin reparación).
     */
    public function up(): void
    {
        Schema::create('abonos', function (Blueprint $table) {
            $table->id();

            // FK a la reparación — si se borra la reparación, se borran sus abonos
            $table->foreignId('reparacion_id')->constrained('reparaciones')->cascadeOnDelete();

            // Cuánto abonó el cliente en este pago
            $table->decimal('monto', 10, 2);

            // Nota opcional (ej: "Pagó la mitad, vuelve el viernes")
            $table->string('nota', 255)->nullable();

            // created_at registra automáticamente cuándo se hizo el abono
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonos');
    }
};
