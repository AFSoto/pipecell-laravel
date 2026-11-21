<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoReparacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReparacionRequest;
use App\Http\Requests\UpdateReparacionRequest;
use App\Models\Caja;
use App\Models\Notificacion;
use App\Models\Producto;
use App\Models\Reparacion;
use App\Services\NotificacionService;
use App\Services\ReparacionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Controlador de Reparaciones.
 *
 * Versión simplificada: sin búsqueda de clientes.
 * Nombre y teléfono van directo en el formulario.
 */
class ReparacionController extends Controller
{
    public function __construct(
        private ReparacionService $reparacionService,
        private NotificacionService $notificacionService
    ) {}

    /**
     * Lista las reparaciones aplicando filtros
     * de estado, fechas y búsqueda general.
     */
    public function index(Request $request)
    {
        // Obtiene el estado desde la URL.
        // Por defecto se usa "en_proceso".
        $estado  = $request->input('estado', 'en_proceso');

        // Obtiene el periodo de tiempo seleccionado.
        // Por defecto se usa "mes".
        $periodo = $request->input('periodo', 'mes');

        // Texto ingresado en el buscador
        $buscar  = $request->input('buscar');

        // Fecha inicial personalizada
        $desde   = $request->input('desde');

        // Fecha final personalizada
        $hasta   = $request->input('hasta');

        // Solo en_proceso no requiere filtro de fechas
        // (se quiere ver todo lo que está pendiente sin importar cuándo entró)
        $sinFiltroFecha = $estado === 'en_proceso';


        // Construcción inicial de la consulta
        $query = Reparacion::with(['caja', 'abonos', 'repuestos.producto', 'ultimoWhatsapp'])

            // Filtra por estado si no es "todos"
            ->when($estado && $estado !== 'todos', fn($q) => $q->where('estado', $estado))

            // Aplica búsqueda únicamente si tiene
            // al menos 2 caracteres
            ->when(strlen($buscar ?? '') >= 2, function ($q) use ($buscar) {

                // Agrupa las búsquedas OR
                $q->where(function ($sub) use ($buscar) {

                    // Busca coincidencias por nombre
                    $sub->where('nombre_cliente', 'like', "%{$buscar}%")

                        // Busca coincidencias por teléfono
                        ->orWhere('telefono_cliente', 'like', "%{$buscar}%")

                        // Busca coincidencias por marca
                        ->orWhere('marca', 'like', "%{$buscar}%")

                        // Busca coincidencias por modelo
                        ->orWhere('modelo', 'like', "%{$buscar}%");
                });
            })

            // Ordena por fecha más reciente
            ->latest();

        // Solo aplica filtros de fecha
        // cuando el estado lo requiere
        if (! $sinFiltroFecha) {

            // Filtrar reparaciones del día actual
            if ($periodo === 'hoy') {

                $query->whereDate('created_at', today());

                // Filtrar reparaciones de la semana actual
            } elseif ($periodo === 'semana') {

                $query->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);

                // Filtrar reparaciones del mes pasado
            } elseif ($periodo === 'mes_pasado') {

                $mesAnterior = now()->subMonthNoOverflow();

                $query->whereMonth('created_at', $mesAnterior->month)
                    ->whereYear('created_at', $mesAnterior->year);

                // Filtrar últimos 3 meses
            } elseif ($periodo === 'trimestre') {

                $query->whereBetween('created_at', [
                    now()->subMonths(3)->startOfDay(),
                    now()->endOfDay()
                ]);

                // Filtrar año actual
            } elseif ($periodo === 'anio') {

                $query->whereYear('created_at', now()->year);

                // Filtrar rango personalizado
            } elseif ($periodo === 'personalizado' && $desde && $hasta) {

                $query->whereBetween('created_at', [
                    $desde,
                    $hasta . ' 23:59:59'
                ]);

                // Filtro por defecto: mes actual
            } else {

                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
            }
        }


        // Si existe búsqueda activa,
        // obtiene todos los resultados.
        // Si no, usa paginación.
        $reparaciones = strlen($buscar ?? '') >= 2
            ? $query->get()
            : $query->paginate(10);

        // Contadores para mostrar estadísticas
        $contadores = [

            // Cantidad en proceso
            'en_proceso' => Reparacion::where('estado', 'en_proceso')->count(),

            // Cantidad arreglada
            'arreglado'  => Reparacion::where('estado', 'arreglado')->count(),

            // Cantidad entregada
            'entregado'  => Reparacion::where('estado', 'entregado')->count(),
        ];

        // Obtiene cajas disponibles ordenadas
        // por grupo y número
        $cajasLibres = Caja::libres()
            ->orderBy('grupo')
            ->orderBy('numero')
            ->get();

        // Displays (pantallas activas) para el campo "¿Se usó display?"
        // de los modales de crear y editar
        $displays = $this->consultaDisplays()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'stock']);

        // Retorna la vista principal
        // junto con toda la información necesaria
        return view('admin.reparaciones.index', compact(
            'reparaciones',
            'cajasLibres',
            'contadores',
            'displays'
        ));
    }


    /**
     * Persiste los cambios de una reparación existente.
     *
     * UpdateReparacionRequest valida los datos antes de entrar aquí.
     * Solo los campos declarados en sus rules() pueden actualizarse,
     * lo que protege contra edición accidental de caja_id o estado.
     */
    public function update(UpdateReparacionRequest $request, Reparacion $reparacion)
    {
        try {
            $reparacion = $this->reparacionService->actualizar(
                $reparacion,
                $request->validated(),
                $request->user()->id
            );

            $repuesto = $reparacion->repuestos->first();

            return response()->json([
                'success' => true,
                'message' => "Reparación #{$reparacion->id} actualizada con éxito.",
                'data'    => [
                    'id'                => $reparacion->id,
                    'nombre_cliente'    => $reparacion->nombre_cliente,
                    'telefono_cliente'  => $reparacion->telefono_cliente,
                    'tiene_whatsapp'    => $reparacion->tiene_whatsapp,
                    'marca'             => $reparacion->marca,
                    'modelo'            => $reparacion->modelo,
                    'descripcion_falla' => $reparacion->descripcion_falla,
                    // Mismo recorte que la celda de la tabla: así el JS no lo repite
                    'descripcion_corta' => $reparacion->descripcionCorta(),
                    'valor_total'       => (int) $reparacion->valor_total,
                    'costo_repuestos'   => (int) $reparacion->costo_repuestos,
                    'display_id'        => $repuesto?->producto_id,
                    'display_nombre'    => $repuesto?->producto?->nombre,
                    'display_cantidad'  => $repuesto?->cantidad ?? 1,
                ],
                // Stock actual de todos los displays, para refrescar los selects sin
                // recargar (incluye cambios que hicieron otras personas o las ventas)
                'stocks'  => $this->consultaDisplays()->pluck('stock', 'id'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Guarda una nueva reparación.
     */
    public function store(StoreReparacionRequest $request)
    {
        try {
            $reparacion = $this->reparacionService->crear($request->validated(), $request->user()->id);

            $mensaje  = 'Reparación registrada con éxito.';
            $repuesto = $reparacion->repuestos->first(); // crear() ya lo deja cargado

            if ($repuesto) {
                $unidades = $repuesto->cantidad === 1 ? '1 unidad' : "{$repuesto->cantidad} unidades";
                $mensaje .= " Se descontó {$unidades} de «{$repuesto->producto->nombre}» del inventario.";
            }

            return redirect()
                ->route('admin.reparaciones.index')
                ->with('success', $mensaje);
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.reparaciones.index')
                ->with('error', 'Error al registrar la reparación: ' . $e->getMessage());
        }
    }

    /**
     * Actualiza el estado via AJAX.
     */
    public function actualizarEstado(Request $request, Reparacion $reparacion)
    {
        $request->validate([
            'estado' => ['required', 'in:en_proceso,arreglado,entregado'],
        ]);

        $estadoAnterior = $reparacion->estado;

        try {
            $reparacion = $this->reparacionService->actualizarEstado(
                $reparacion,
                $request->estado
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage(),
            ], 500);
        }

        // El estado ya quedó guardado. El aviso solo sale cuando el equipo pasa
        // de "En proceso" a "Arreglado" (no en correcciones como entregado → arreglado).
        $notificacion = null;

        if ($estadoAnterior === EstadoReparacion::EnProceso && $reparacion->estado === EstadoReparacion::Arreglado) {
            $notificacion = $this->avisarReparacionLista($reparacion, $request->user()->id);
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Estado actualizado correctamente.',
            'notificacion' => $notificacion,
            'whatsapp'     => $this->estadoWhatsapp($reparacion),
        ]);
    }

    /**
     * Envía (o reintenta) el WhatsApp de "reparación lista" desde el botón de la fila.
     *
     * Si el aviso ya había salido (por ejemplo, otra persona lo envió desde su pantalla),
     * no se manda otra vez: se devuelve el estado actual para poner la fila al día.
     */
    public function enviarWhatsapp(Request $request, Reparacion $reparacion)
    {
        if ($reparacion->estado !== EstadoReparacion::Arreglado) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede avisar al cliente cuando la reparación está en "Arreglado".',
            ], 422);
        }

        $notificacion = $this->avisarReparacionLista($reparacion, $request->user()->id);

        return response()->json([
            'success'      => true,
            'message'      => $notificacion ? null : 'Este aviso ya se había enviado: no se mandó otra vez.',
            'notificacion' => $notificacion,
            'whatsapp'     => $this->estadoWhatsapp($reparacion),
        ]);
    }

    /**
     * Último intento de aviso por WhatsApp, como lo pinta la fila (null si no hay).
     */
    private function estadoWhatsapp(Reparacion $reparacion): ?array
    {
        return $reparacion->ultimoWhatsapp()->first()?->paraVista();
    }

    /**
     * Displays que se ofrecen en los modales: pantallas activas del inventario.
     */
    private function consultaDisplays(): Builder
    {
        return Producto::activos()->pantallas();
    }

    /**
     * Envía el aviso y devuelve los datos para la vista. Nunca lanza excepción:
     * un problema con WhatsApp no debe dañar el cambio de estado que ya se guardó.
     */
    private function avisarReparacionLista(Reparacion $reparacion, int $userId): ?array
    {
        try {
            return $this->notificacionService
                ->notificarReparacionLista($reparacion, $userId)
                ?->paraVista();
        } catch (\Throwable $e) {
            // Los errores al enviar ya quedan guardados como "fallido" en el servicio;
            // aquí solo llega lo que impidió hasta guardar el intento.
            report($e);

            return Notificacion::vistaErrorInterno();
        }
    }

    /**
     * Registra un abono via AJAX.
     */
    public function registrarAbono(Request $request, Reparacion $reparacion)
    {
        $request->validate([
            'monto' => ['required', 'numeric', 'min:1', 'regex:/^\d+(\.\d{1,2})?$/'],
            'nota'  => ['nullable', 'string', 'max:255'],
        ]);

        $saldo = $reparacion->saldo_pendiente;

        if ($saldo <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Esta reparación ya está pagada completamente.',
            ], 422);
        }

        if ($request->monto > $saldo) {
            return response()->json([
                'success' => false,
                'message' => "El monto supera el saldo pendiente de $" . number_format($saldo, 0, ',', '.') . ".",
            ], 422);
        }

        try {
            $this->reparacionService->registrarAbono(
                $reparacion,
                $request->monto,
                $request->nota
            );

            return response()->json([
                'success' => true,
                'message' => 'Abono registrado correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar abono: ' . $e->getMessage(),
            ], 500);
        }
    }
}
