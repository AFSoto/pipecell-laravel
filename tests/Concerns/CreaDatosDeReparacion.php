<?php

namespace Tests\Concerns;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Reparacion;
use App\Models\User;
use App\Services\ReparacionService;

/**
 * Helpers para crear datos de prueba sin depender de Faker.
 */
trait CreaDatosDeReparacion
{
    protected function crearUsuario(): User
    {
        return User::create([
            'nombre'   => 'Admin Pruebas',
            'email'    => 'admin@pruebas.test',
            'password' => 'password',
            'role'     => 'admin',
            'estado'   => 'activo',
        ]);
    }

    protected function crearCaja(string $grupo = 'A', int $numero = 1): Caja
    {
        return Caja::create(['grupo' => $grupo, 'numero' => $numero, 'estado' => 'libre']);
    }

    protected function crearPantalla(string $nombre = 'Pantalla Samsung A10', int $stock = 5, int $precioCompra = 40000): Producto
    {
        $categoria = Categoria::firstOrCreate(
            ['nombre' => 'Repuestos - Pantallas'],
            ['estado' => 'activo']
        );

        return Producto::create([
            'categoria_id'  => $categoria->id,
            'nombre'        => $nombre,
            'marco'         => false,
            'precio_compra' => $precioCompra,
            'precio_venta'  => $precioCompra * 2,
            'stock'         => $stock,
            'stock_minimo'  => 1,
            'estado'        => 'activo',
        ]);
    }

    /**
     * Crea una reparación con el servicio real (incluye display si se pasa).
     * Si no se indica caja, crea una nueva libre (A1, A2, ...).
     */
    protected function crearReparacion(User $usuario, array $datos = []): Reparacion
    {
        $datos['caja_id'] ??= $this->crearCaja('A', Caja::count() + 1)->id;

        return app(ReparacionService::class)->crear(array_merge([
            'nombre_cliente'   => 'Juan Pérez',
            'telefono_cliente' => '310 123 4567',
            'tiene_whatsapp'   => true,
            'marca'            => 'Samsung',
            'modelo'           => 'A10',
            'valor_total'      => 150000,
            'abono_inicial'    => 50000,
        ], $datos), $usuario->id);
    }
}
