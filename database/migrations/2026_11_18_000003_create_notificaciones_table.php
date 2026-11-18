<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de mensajes enviados (o intentados) al cliente.
 *
 * Cada intento deja un registro: enviado, fallido u omitido (con el motivo).
 * 'canal' hoy solo es 'whatsapp'; queda listo para agregar 'sms' después.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reparacion_id')->constrained('reparaciones')->cascadeOnDelete();

            // Quién disparó el envío (al cambiar el estado o con el botón de reintentar)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('canal', 20);            // whatsapp | sms
            $table->string('tipo', 40);             // reparacion_lista
            $table->string('telefono', 20)->nullable();
            $table->string('estado', 20);           // enviado | fallido | omitido

            // ID del mensaje que devuelve Meta (wamid). Indexado para el futuro
            // webhook de confirmación de entrega.
            $table->string('mensaje_id', 150)->nullable()->index();

            // Motivo del omitido o error devuelto por el proveedor
            $table->string('detalle', 500)->nullable();

            $table->timestamps();

            $table->index(['reparacion_id', 'canal', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
