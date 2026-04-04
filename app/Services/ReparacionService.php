<?php

namespace App\Services;

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

            // Verificamos contra reparaciones activas reales, no contra el campo estado
            // que puede estar desincronizado (ej: reparación volvió de entregado a arreglado
            // sin que el campo estado de la caja se actualizara).
            if (! $caja->estaLibre()) {
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

            // Regla de negocio: una caja solo puede tener una reparación activa al mismo tiempo.
            // Solo aplica cuando el destino es un estado activo (en_proceso o arreglado).
            // Se excluye la propia reparación para no bloquear transiciones válidas
            // como en_proceso → arreglado, donde ella misma ya figura como activa en BD.
            $estadosActivos = [EstadoReparacion::EnProceso->value, EstadoReparacion::Arreglado->value];

            if (in_array($nuevoEstado, $estadosActivos)) {
                $otraActiva = $reparacion->caja
                    ->reparacionesActivas()
                    ->where('id', '!=', $reparacion->id)
                    ->exists();

                if ($otraActiva) {
                    throw new \RuntimeException(
                        "La caja {$reparacion->caja->nombre_display} ya tiene otra reparación activa. " .
                        "Entrega esa reparación antes de reactivar esta."
                    );
                }
            }

            // Regla de negocio: no se puede entregar si hay saldo pendiente.
            if ($nuevoEstado === 'entregado' && ! $reparacion->estaPagada()) {
                $pendiente = number_format($reparacion->saldo_pendiente, 0, ',', '.');
                throw new \RuntimeException(
                    "No se puede marcar como entregado: hay un saldo pendiente de \${$pendiente}."
                );
            }

            $reparacion->update([
                'estado' => $nuevoEstado,
                'fecha_entrega' => $nuevoEstado === 'entregado' ? now() : $reparacion->fecha_entrega,
            ]);

            // Sincronizar el campo estado de la caja con el estado real de la reparación.
            // 'entregado' → la reparación termina → caja libre.
            // Cualquier otro estado (en_proceso, arreglado) → reparación activa → caja ocupada.
            // Esto cubre el caso entregado → arreglado que antes dejaba la caja como libre.
            if ($nuevoEstado === 'entregado') {
                $reparacion->caja->liberar();
            } else {
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
