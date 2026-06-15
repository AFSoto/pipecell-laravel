<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla para almacenar las imágenes asociadas a un producto.
     *
     * Un producto puede tener varias fotos (galería), pero solo una
     * puede ser la imagen principal que se muestra en el listado.
     * Solo se guarda la ruta del archivo; el binario vive en storage/.
     */
    public function up(): void
    {
        Schema::create('producto_imagenes', function (Blueprint $table) {
            $table->id();

            // FK al producto dueño de esta imagen
            // cascadeOnDelete: al borrar un producto se borran sus imágenes
            $table->foreignId('producto_id')
                ->constrained('productos')
                ->cascadeOnDelete();

            // Ruta relativa dentro de storage (e.g. "productos/pantalla-iphone.jpg")
            $table->string('path', 255);

            // Indica si esta foto es la imagen principal del producto
            // Solo debería haber una por producto, se controla desde la app
            $table->boolean('es_principal')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_imagenes');
    }
};
