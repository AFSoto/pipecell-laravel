<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Abono;
use App\Models\Reparacion;
use Illuminate\Database\Seeder;

class ReparacionSeeder extends Seeder
{
    /**
     * Ejecuta el seeder principal.
     */
    public function run(): void
    {
        // Fecha inicial del rango
        $inicio = Carbon::create(2026, 1, 1);

        // Fecha final del rango
        $fin = Carbon::create(2027, 12, 31);

        // Recorre cada día dentro del rango definido
        while ($inicio <= $fin) {

            // Genera 15 reparaciones por día
            for ($i = 0; $i < 15; $i++) {

                // Genera una fecha aleatoria dentro del día actual
                $fecha = $inicio
                    ->copy()
                    ->setHour(rand(8, 18))
                    ->setMinute(rand(0, 59))
                    ->setSecond(rand(0, 59));

                // Crea la reparación con fechas personalizadas
                $reparacion = Reparacion::factory()->create([
                    'created_at' => $fecha,
                    'updated_at' => $fecha,
                ]);

                // Genera los abonos relacionados a la reparación
                $this->crearAbonos($reparacion);
            }

            // Avanza al siguiente día
            $inicio->addDay();
        }
    }

    /**
     * Genera abonos para una reparación.
     *
     * La suma total de los abonos será igual
     * al valor total de la reparación.
     */
    private function crearAbonos(Reparacion $reparacion): void
    {
        // Obtiene el valor total de la reparación
        $total = $reparacion->valor_total;

        // Cantidad aleatoria de abonos
        $cantidadAbonos = rand(1, 5);

        // Variable para controlar el dinero restante
        $restante = $total;

        // Recorre la cantidad de abonos a generar
        for ($i = 1; $i <= $cantidadAbonos; $i++) {

            // Si es el último abono,
            // se asigna todo el valor restante
            if ($i == $cantidadAbonos) {

                $monto = $restante;

            } else {

                // Genera un monto aleatorio
                // sin superar la mitad restante
                $monto = rand(
                    10000,
                    max(10000, intval($restante / 2))
                );

                // Resta el monto generado
                // al valor pendiente
                $restante -= $monto;
            }

            // Crea el abono asociado a la reparación
            Abono::factory()->create([
                'reparacion_id' => $reparacion->id,
                'monto' => $monto,

                // Fecha aleatoria después de la reparación
                'created_at' => $reparacion->created_at
                    ->copy()
                    ->addDays(rand(0, 15)),
            ]);
        }
    }
}
