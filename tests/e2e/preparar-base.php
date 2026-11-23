<?php

/**
 * Crea la base SQLite temporal de las pruebas de navegador (tests/e2e). La llama correr.mjs
 * con el entorno de prueba; nunca toca MySQL.
 *
 *   php tests/e2e/preparar-base.php normal   productos y 15 reparaciones para las pruebas
 *   php tests/e2e/preparar-base.php grande   3.000 reparaciones de un año (rendimiento)
 *
 * Usuario de prueba: e2e@pruebas.test / clave-e2e.
 */

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Reparacion;
use App\Models\User;
use App\Services\ReparacionService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/seguridad.php';

$archivo = baseTemporalOAbortar();
$tamano  = $argv[1] ?? 'normal';

@unlink($archivo);
touch($archivo);

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

confirmarConexionTemporal($archivo);

Artisan::call('migrate', ['--force' => true]);

$usuario = User::create([
    'nombre' => 'Prueba E2E', 'email' => 'e2e@pruebas.test', 'password' => 'clave-e2e', 'role' => 'admin', 'estado' => 'activo',
]);

if ($tamano === 'grande') {
    reparacionesDeUnAnio();
} else {
    productosDePrueba();
    reparacionesDePrueba($usuario);
}

printf("Base lista: %d productos, %d reparaciones\n", Producto::count(), Reparacion::count());

/** 19 productos: accesorios, dos "Batería iPhone 14" de distinta marca y dos pantallas. */
function productosDePrueba(): void
{
    $accesorios = Categoria::create(['nombre' => 'Accesorios', 'estado' => 'activo']);
    $baterias   = Categoria::create(['nombre' => 'Repuestos - Baterías', 'estado' => 'activo']);
    $pantallas  = Categoria::create(['nombre' => 'Repuestos - Pantallas', 'estado' => 'activo']);

    $crear = fn (Categoria $categoria, string $nombre, int $stock, int $minimo, ?string $marca = null, array $extra = []) =>
        Producto::create(array_merge([
            'categoria_id' => $categoria->id, 'nombre' => $nombre, 'marca' => $marca, 'precio_venta' => 50000,
            'stock' => $stock, 'stock_minimo' => $minimo, 'estado' => 'activo',
        ], $extra));

    foreach (['Adaptador OTG', 'Audífonos Bluetooth', 'Cable Lightning 1m', 'Cable USB-C 2m', 'Cargador 20W USB-C',
              'Cargador de carro', 'Cargador inalámbrico', 'Forro silicona', 'Lápiz táctil', 'Memoria microSD 64GB',
              'Pop socket', 'Protector de cámara', 'Soporte para carro', 'Vidrio templado 9D'] as $i => $nombre) {
        $crear($accesorios, $nombre, [3, 12, 0, 25, 1][$i % 5], 3);
    }

    $crear($baterias, 'Batería iPhone 14', 4, 2, 'Deji');
    $crear($baterias, 'Batería iPhone 14', 1, 2, 'Ampsentrix');
    $crear($baterias, 'Batería Samsung A10', 6, 2, 'Deji');
    $crear($pantallas, 'Pantalla LG K40', 3, 1, 'LG', ['referencia' => 'K40', 'marco' => false]);
    $crear($pantallas, 'Pantalla Samsung A10', 5, 1, 'Samsung', ['referencia' => 'A10', 'marco' => true]);
}

/**
 * 15 reparaciones: Laura Gómez, Pedro Ruiz (Xiaomi, en "Arreglado" sin aviso: su fila
 * muestra "Enviar WhatsApp"), Ana Torres y Cliente 01 a 12 (Samsung A10). Teléfonos
 * "310 555 0000" a "310 555 0014", con espacios como el ejemplo del formulario.
 */
function reparacionesDePrueba(User $usuario): void
{
    $clientes = ['Laura Gómez', 'Pedro Ruiz', 'Ana Torres'];
    for ($n = 1; $n <= 12; $n++) {
        $clientes[] = sprintf('Cliente %02d', $n);
    }

    foreach ($clientes as $i => $cliente) {
        $caja = Caja::create(['grupo' => 'A', 'numero' => $i + 1, 'estado' => 'libre']);

        app(ReparacionService::class)->crear([
            'nombre_cliente' => $cliente, 'telefono_cliente' => '310 555 ' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            'tiene_whatsapp' => false, 'caja_id' => $caja->id, 'marca' => $i === 1 ? 'Xiaomi' : 'Samsung',
            'modelo' => $i === 1 ? 'Redmi Note 12' : 'A10', 'valor_total' => 150000, 'abono_inicial' => 50000,
        ], $usuario->id);
    }

    Reparacion::where('nombre_cliente', 'Pedro Ruiz')->update(['estado' => 'arreglado']);
}

/** Un año de un local con movimiento: 3.000 reparaciones (200 en proceso), la mitad con abono. */
function reparacionesDeUnAnio(): void
{
    $caja = Caja::create(['grupo' => 'A', 'numero' => 1, 'estado' => 'libre']);

    mt_srand(7);
    $nombres   = ['María', 'José', 'Luis', 'Carlos', 'Ana', 'Andrés', 'Andrea', 'Juan', 'Sandra', 'Diana', 'Jorge', 'Paola',
                  'Carolina', 'Ricardo', 'Fernando', 'Marcela', 'Mario', 'Laura', 'Camilo', 'Valentina', 'Santiago', 'Daniela'];
    $apellidos = ['Gómez', 'Rodríguez', 'Martínez', 'García', 'López', 'Hernández', 'Sánchez', 'Ramírez', 'Torres', 'Castro',
                  'Moreno', 'Rojas', 'Vargas', 'Ruiz', 'Ortiz', 'Jiménez', 'Mejía', 'Cárdenas'];
    $equipos   = [['Samsung', ['A10', 'A12', 'A14', 'A24', 'A54', 'S21']], ['Samsung', ['A05s', 'A15', 'A34']],
                  ['Xiaomi', ['Redmi Note 12', 'Redmi 13C', 'Poco X5']], ['Xiaomi', ['Redmi Note 13', 'Redmi A3']],
                  ['Motorola', ['G32', 'E13', 'G54']], ['Apple', ['iPhone 11', 'iPhone 12', 'iPhone 13']],
                  ['Huawei', ['Y9', 'P30 Lite']], ['Oppo', ['A17', 'A58']]];
    $prefijos  = ['300', '301', '304', '310', '311', '313', '315', '316', '318', '320', '321'];

    $inicio = now()->startOfYear();
    $dias   = (int) $inicio->diffInDays(now());
    $filas  = [];

    for ($n = 0; $n < 3000; $n++) {
        [$marca, $modelos] = $equipos[mt_rand(0, count($equipos) - 1)];
        $creada = $inicio->copy()->addDays(mt_rand(0, $dias))->setTime(mt_rand(8, 19), mt_rand(0, 59));
        $estado = $n < 200 ? 'en_proceso' : ($n < 300 ? 'arreglado' : 'entregado');

        $filas[] = [
            'nombre_cliente'    => $nombres[mt_rand(0, count($nombres) - 1)] . ' ' . $apellidos[mt_rand(0, count($apellidos) - 1)],
            'telefono_cliente'  => $prefijos[mt_rand(0, count($prefijos) - 1)] . mt_rand(1000000, 9999999),
            'tiene_whatsapp'    => true,
            'caja_id'           => $caja->id,
            'marca'             => $marca,
            'modelo'            => $modelos[mt_rand(0, count($modelos) - 1)],
            'descripcion_falla' => ['Cambio de pantalla', 'Cambio de batería', 'No carga', 'Se mojó', 'Pin de carga'][mt_rand(0, 4)],
            'valor_total'       => mt_rand(8, 60) * 5000,
            'estado'            => $estado,
            'fecha_entrega'     => $estado === 'entregado' ? $creada->copy()->addDays(2) : null,
            'created_at'        => $creada,
            'updated_at'        => $creada,
        ];
    }

    DB::transaction(function () use ($filas) {
        foreach (array_chunk($filas, 200) as $grupo) {
            DB::table('reparaciones')->insert($grupo);
        }

        $abonos = DB::table('reparaciones')->whereRaw('id % 2 = 0')->pluck('id')->map(fn ($id) => [
            'reparacion_id' => $id, 'monto' => 20000, 'nota' => 'Abono inicial', 'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ($abonos->chunk(200) as $grupo) {
            DB::table('abonos')->insert($grupo->all());
        }
    });
}
