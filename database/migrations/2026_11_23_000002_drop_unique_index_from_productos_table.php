<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quita el índice único productos_unique (categoria_id, tipo_producto_id, marco, nombre).
 *
 * La regla de "producto repetido" ahora incluye la marca y queda solo en la validación
 * (Concerns\ValidaNombreUnicoProducto): dos "Batería iPhone 14" de distinta marca pueden
 * convivir. El índice no servía para eso:
 *   - contaba los productos desactivados: al volver a crear una pantalla con tipo que se
 *     había desactivado, el formulario la dejaba pasar y la base la rechazaba con un
 *     error técnico (SQLSTATE[23000]);
 *   - MySQL no compara filas con algún valor NULL, así que solo cubría pantallas con tipo.
 *     Con la marca, vacía en casi todos los productos, cubriría todavía menos.
 *
 * La clave foránea de categoria_id sigue con su propio índice (productos_categoria_id_index,
 * de create_productos_table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('productos_unique');
        });
    }

    /**
     * Vuelve a crear el índice. Falla si para entonces hay productos repetidos con tipo y
     * marco (contando los desactivados): habría que corregirlos antes de revertir.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->unique(['categoria_id', 'tipo_producto_id', 'marco', 'nombre'], 'productos_unique');
        });
    }
};
