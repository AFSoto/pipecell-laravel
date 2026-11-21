<?php

namespace App\Services;

use App\Enums\EstadoReparacion;
use App\Events\ReparacionCambio;
use App\Exceptions\ConflictoEdicionException;
use App\Models\Abono;
use App\Models\Caja;
use App\Models\Producto;
use App\Models\Reparacion;
use App\Models\ReparacionRepuesto;
use App\Support\TiempoReal;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Service para la lógica de negocio de reparaciones.
 *
 * Versión simplificada: sin tabla de clientes.
 * Nombre y teléfono van directo en la reparación.
 */
class ReparacionService
{
    /** Motivo del movimiento de stock cuando un display se usa en una reparación. */
    public const MOTIVO_USO = 'Uso en reparación';

    /** Motivo del movimiento de stock cuando un display vuelve al inventario. */
    public const MOTIVO_DEVOLUCION = 'Devolución de reparación';

    public function __construct(
        private ProductoService $productoService
    ) {}

    /**
     * Crea una nueva reparación.
     *
     * Dentro de una transacción:
     * 1. Crea la reparación con los datos del cliente directo
     * 2. Ocupa la caja
     * 3. Registra el abono inicial si hay
     * 4. Descuenta del inventario el display usado, si se indicó
     *
     * Si no hay stock suficiente del display, la transacción revierte todo.
     */
    public function crear(array $datos, int $userId): Reparacion
    {
        return DB::transaction(function () use ($datos, $userId) {

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
                'tiene_whatsapp'    => $datos['tiene_whatsapp'] ?? true,
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

            // ── 4. Display usado (si se indicó) ──
            // Solo mueve inventario: el costo de Repuestos lo escribe el usuario,
            // porque el valor total ya incluye repuestos y mano de obra.
            $repuestos = collect();

            if (! empty($datos['display_id'])) {
                $repuestos->push($this->sincronizarDisplay(
                    $reparacion,
                    (int) $datos['display_id'],
                    (int) ($datos['display_cantidad'] ?? 1),
                    $userId
                ));
            }

            // Las otras pantallas actualizan contadores y cajas libres (se envía al confirmar)
            TiempoReal::avisar(new ReparacionCambio($reparacion->id, ReparacionCambio::CREADA));

            // Queda cargado para que el controlador no lo vuelva a consultar
            return $reparacion->setRelation('repuestos', $repuestos);
        });
    }

    /**
     * Actualiza los datos editables de una reparación y, si vienen los campos
     * del display, sincroniza el inventario. Todo en una sola transacción.
     *
     * Si la petición no trae 'display_id' (clientes con la página vieja abierta),
     * el display y el inventario no se tocan.
     *
     * Si MySQL detecta un bloqueo cruzado con otra transacción, la reintenta
     * hasta 3 veces antes de fallar.
     *
     * @param  array|null  $original  Los datos con que se abrió el modal (data-edit). Si
     *                                otra persona cambió alguno de esos campos desde
     *                                entonces, no se guarda nada. null = no se revisa
     *                                (páginas abiertas antes de esta versión).
     *
     * @throws ConflictoEdicionException
     */
    public function actualizar(Reparacion $reparacion, array $datos, int $userId, ?array $original = null): Reparacion
    {
        return DB::transaction(function () use ($reparacion, $datos, $userId, $original) {

            // Releer con bloqueo: si dos personas guardan la misma reparación a la vez,
            // la segunda espera a que termine la primera y parte de lo que esta dejó.
            // Sin esto, las dos leerían el mismo display y lo devolverían dos veces.
            $reparacion = Reparacion::lockForUpdate()->findOrFail($reparacion->id);

            // Nunca pisar sin avisar lo que otra persona cambió mientras el modal estaba abierto
            if ($original !== null && $reparacion->cambioDesde($original)) {
                throw new ConflictoEdicionException();
            }

            $reparacion->update(Arr::except($datos, ['usa_display', 'display_id', 'display_cantidad']));

            if (array_key_exists('display_id', $datos)) {
                $this->sincronizarDisplay(
                    $reparacion,
                    $datos['display_id'] ? (int) $datos['display_id'] : null,
                    (int) ($datos['display_cantidad'] ?? 1),
                    $userId
                );
            }

            TiempoReal::avisar(new ReparacionCambio($reparacion->id, ReparacionCambio::ACTUALIZADA));

            return $reparacion->fresh(['repuestos.producto']);
        }, 3);
    }

    /**
     * Deja el display de la reparación como se pidió y ajusta el inventario
     * solo por la diferencia, usando ProductoService::ajustarStock() (que bloquea
     * la fila del producto y registra el MovimientoStock):
     *
     * - No tenía y ahora sí   → salida de $cantidad
     * - Tenía y se quitó      → entrada (devolución) de lo que tenía
     * - Mismo display         → salida o entrada solo por la diferencia de cantidad
     * - Otro display          → devolución del anterior + salida del nuevo
     *
     * @return ReparacionRepuesto|null El display que quedó asignado (null si ninguno)
     */
    public function sincronizarDisplay(Reparacion $reparacion, ?int $productoId, int $cantidad, int $userId): ?ReparacionRepuesto
    {
        return DB::transaction(function () use ($reparacion, $productoId, $cantidad, $userId) {

            $actual = $reparacion->repuestos()->with('producto')->first();
            $nota   = "Reparación #{$reparacion->id} – {$reparacion->nombre_cliente}";

            // Nada antes y nada ahora
            if ($productoId === null && $actual === null) {
                return null;
            }

            // Se quitó el display: vuelve completo al inventario
            if ($productoId === null) {
                $this->moverStock($actual->producto, 'entrada', $actual->cantidad, self::MOTIVO_DEVOLUCION, $nota, $userId);
                $actual->delete();

                return null;
            }

            // Mismo display: solo se mueve la diferencia de cantidad
            if ($actual && $actual->producto_id === $productoId) {
                $diferencia = $cantidad - $actual->cantidad;

                if ($diferencia > 0) {
                    $this->moverStock($actual->producto, 'salida', $diferencia, self::MOTIVO_USO, $nota, $userId);
                } elseif ($diferencia < 0) {
                    $this->moverStock($actual->producto, 'entrada', -$diferencia, self::MOTIVO_DEVOLUCION, $nota, $userId);
                }

                $actual->update(['cantidad' => $cantidad]);

                return $actual;
            }

            // Otro display (o no tenía): devolver el anterior y descontar el nuevo
            if ($actual) {
                // Bloquear los dos productos en orden de id antes de mover stock: si dos
                // reparaciones intercambian displays (A→B y B→A) al mismo tiempo, las dos
                // piden los bloqueos en el mismo orden y no se traban entre sí.
                Producto::whereIn('id', [$actual->producto_id, $productoId])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $this->moverStock($actual->producto, 'entrada', $actual->cantidad, self::MOTIVO_DEVOLUCION, $nota, $userId);
            }

            $producto = Producto::findOrFail($productoId);
            $this->moverStock($producto, 'salida', $cantidad, self::MOTIVO_USO, $nota, $userId);

            $datosRepuesto = [
                'producto_id'    => $producto->id,
                'cantidad'       => $cantidad,
                'costo_unitario' => $producto->precio_compra,
            ];

            if ($actual) {
                $actual->update($datosRepuesto);

                return $actual->fresh('producto');
            }

            return $reparacion->repuestos()->create($datosRepuesto)->setRelation('producto', $producto);
        });
    }

    private function moverStock(Producto $producto, string $operacion, int $cantidad, string $motivo, string $nota, int $userId): void
    {
        $this->productoService->ajustarStock($producto, [
            'operacion'  => $operacion,
            'cantidad'   => $cantidad,
            'nuevo_tipo' => $motivo,
            'nota'       => $nota,
        ], $userId);
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

            // Las otras pantallas cambian la fila, los contadores y las cajas libres;
            // si quedó "arreglado", muestran el aviso de reparación lista
            TiempoReal::avisar(new ReparacionCambio($reparacion->id, ReparacionCambio::ESTADO, $nuevoEstado));

            return $reparacion->fresh();
        });
    }

    /**
     * Registra un nuevo abono.
     *
     * La transacción + lockForUpdate garantiza que dos peticiones concurrentes
     * no puedan ambas pasar la validación de saldo y registrar abonos que en
     * total superen el valor de la reparación.
     */
    public function registrarAbono(Reparacion $reparacion, float $monto, ?string $nota = null): Abono
    {
        return DB::transaction(function () use ($reparacion, $monto, $nota) {

            // Releer con bloqueo exclusivo para cerrar la ventana de race condition
            // entre la validación del controlador y la escritura en BD.
            $reparacion = Reparacion::lockForUpdate()->findOrFail($reparacion->id);

            $saldo = $reparacion->saldo_pendiente;

            if ($saldo <= 0) {
                throw new \RuntimeException('Esta reparación ya está pagada completamente.');
            }

            if ($monto > $saldo) {
                $pendiente = number_format($saldo, 0, ',', '.');
                throw new \RuntimeException(
                    "El monto supera el saldo pendiente de \${$pendiente}."
                );
            }

            $abono = Abono::create([
                'reparacion_id' => $reparacion->id,
                'monto'         => $monto,
                'nota'          => $nota,
            ]);

            TiempoReal::avisar(new ReparacionCambio($reparacion->id, ReparacionCambio::ABONO));

            return $abono;
        });
    }
}
