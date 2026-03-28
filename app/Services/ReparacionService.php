<?php

namespace App\Services;

use App\Enums\EstadoCaja;
use App\Enums\EstadoReparacion;
use App\Models\Abono;
use App\Models\Caja;
use App\Models\Reparacion;
use Illuminate\Support\Facades\DB;

/**
 * Service para la lógica de negocio de reparaciones.
 *
 * Versión simplificada: sin tabla de clientes.
 * Nombre y teléfono van directo en la reparación.
 */
class ReparacionService
{
    /**
     * Crea una nueva reparación.
     *
     * Dentro de una transacción:
     * 1. Crea la reparación con los datos del cliente directo
     * 2. Ocupa la caja
     * 3. Registra el abono inicial si hay
     */
    public function crear(array $datos): Reparacion
    {
        return DB::transaction(function () use ($datos) {

            // ── 0. Segunda línea de defensa contra condiciones de carrera ──
            //
            // lockForUpdate() bloquea la fila de esta caja en la base de datos
            // mientras dure la transacción. Si dos peticiones llegan al mismo
            // tiempo, la segunda esperará a que la primera termine antes de leer
            // el estado de la caja. Así se garantiza que solo una puede ocuparla.
            //
            // Esto complementa la validación del FormRequest: el Request valida
            // antes de entrar aquí, pero entre esa validación y este punto podría
            // haber pasado otra petición. lockForUpdate() cierra esa ventana.
            $caja = Caja::lockForUpdate()->findOrFail($datos['caja_id']);

            // Verificamos el estado dentro de la transacción bloqueada.
            // Si no está libre, lanzamos una excepción que deshace la transacción
            // y el controlador la convierte en mensaje de error para el usuario.
            if ($caja->estado !== EstadoCaja::Libre) {
                throw new \RuntimeException(
                    "La caja {$caja->nombre_display} ya está ocupada. Por favor, selecciona otra."
                );
            }

            // ── 1. Crear la reparación ──
            $reparacion = Reparacion::create([
                'nombre_cliente'    => $datos['nombre_cliente'],
                'telefono_cliente'  => $datos['telefono_cliente'] ?? null,
                'caja_id'           => $datos['caja_id'],
                'marca'             => $datos['marca'],
                'modelo'            => $datos['modelo'] ?? null,
                'descripcion_falla' => $datos['descripcion_falla'] ?? null,
                'valor_total'       => $datos['valor_total'],
                'costo_repuestos'   => $datos['costo_repuestos'] ?? null,
                'estado'            => EstadoReparacion::EnProceso,
            ]);

            // ── 2. Ocupar la caja ──
            // Reutilizamos la instancia ya cargada y bloqueada del paso 0
            $caja->ocupar();

            // ── 3. Registrar abono inicial (si pagó algo) ──
            if (!empty($datos['abono_inicial']) && $datos['abono_inicial'] > 0) {
                Abono::create([
                    'reparacion_id' => $reparacion->id,
                    'monto'         => $datos['abono_inicial'],
                    'nota'          => 'Abono inicial al recibir el equipo',
                ]);
            }

            return $reparacion;
        });
    }

    /**
     * Actualiza el estado de una reparación.
     *
     * Si pasa a 'entregado': libera la caja y registra fecha.
     * Si vuelve a 'en_proceso': ocupa la caja de nuevo.
     */
    public function actualizarEstado(Reparacion $reparacion, string $nuevoEstado): Reparacion
    {
        return DB::transaction(function () use ($reparacion, $nuevoEstado) {

            $reparacion->update([
                'estado' => $nuevoEstado,
                'fecha_entrega' => $nuevoEstado === 'entregado' ? now() : $reparacion->fecha_entrega,
            ]);

            if ($nuevoEstado === 'entregado') {
                $reparacion->caja->liberar();
            }

            if ($nuevoEstado === 'en_proceso' && $reparacion->caja->estaLibre()) {
                $reparacion->caja->ocupar();
            }

            return $reparacion->fresh();
        });
    }

    /**
     * Registra un nuevo abono.
     */
    public function registrarAbono(Reparacion $reparacion, float $monto, ?string $nota = null): Abono
    {
        return Abono::create([
            'reparacion_id' => $reparacion->id,
            'monto'         => $monto,
            'nota'          => $nota,
        ]);
    }
}
