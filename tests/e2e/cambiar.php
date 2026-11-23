<?php

/**
 * "Otra persona" cambia una reparación en la base temporal, como si lo hiciera desde otra
 * pantalla. El aviso de Pusher lo simula la prueba en el navegador.
 *
 *   php tests/e2e/cambiar.php "<nombre del cliente>" <campo> <valor>
 *
 * Imprime el id de la reparación.
 */

use App\Models\Reparacion;

require __DIR__ . '/seguridad.php';

$archivo = baseTemporalOAbortar();

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

confirmarConexionTemporal($archivo);

[, $cliente, $campo, $valor] = $argv;

$reparacion = Reparacion::where('nombre_cliente', $cliente)->firstOrFail();
Reparacion::whereKey($reparacion->id)->update([$campo => $valor, 'updated_at' => now()]);

echo $reparacion->id;
