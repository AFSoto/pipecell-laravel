<?php

/**
 * Datos para la prueba del buscador de display (display.mjs), en la base temporal.
 *
 *   php tests/e2e/pantallas.php preparar
 *       Agrega una caja libre (en la base todas están ocupadas), una «Pantalla Samsung A10»
 *       Genérico sin marco (el mismo nombre que la de la base, que es con marco), una
 *       «Pantalla Motorola G20» sin stock y 60 «Pantalla Xiaomi Redmi Note», para la lista
 *       larga. Imprime el id de la caja.
 *
 *   php tests/e2e/pantallas.php desactivar "<nombre>" <marco: 0 o 1>
 *       Desactiva esa pantalla, como si la desactivaran en Productos.
 */

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\TipoProducto;

require __DIR__ . '/seguridad.php';

$archivo = baseTemporalOAbortar();

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

confirmarConexionTemporal($archivo);

$accion = $argv[1] ?? '';

if ($accion === 'preparar') {
    $pantallas = Categoria::where('nombre', 'Repuestos - Pantallas')->firstOrFail();
    $generico  = TipoProducto::firstOrCreate(['nombre' => 'Genérico']);

    $crear = fn (string $nombre, int $stock, bool $marco, ?int $tipo = null) => Producto::create([
        'categoria_id' => $pantallas->id, 'tipo_producto_id' => $tipo, 'nombre' => $nombre, 'marco' => $marco,
        'precio_venta' => 90000, 'stock' => $stock, 'stock_minimo' => 1, 'estado' => 'activo',
    ]);

    $crear('Pantalla Samsung A10', 4, false, $generico->id);
    $crear('Pantalla Motorola G20', 0, false);

    for ($n = 1; $n <= 60; $n++) {
        $crear(sprintf('Pantalla Xiaomi Redmi Note %02d', $n), 2, true);
    }

    echo Caja::create(['grupo' => 'B', 'numero' => 1, 'estado' => 'libre'])->id;
} elseif ($accion === 'desactivar' && isset($argv[2], $argv[3])) {
    Producto::where('nombre', $argv[2])
        ->where('marco', $argv[3] === '1')
        ->firstOrFail()
        ->update(['estado' => 'inactivo']);
} else {
    fwrite(STDERR, "Uso: pantallas.php preparar | desactivar \"<nombre>\" <marco: 0 o 1>\n");
    exit(1);
}
