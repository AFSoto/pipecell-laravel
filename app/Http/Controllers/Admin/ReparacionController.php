<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoReparacion;
use App\Exceptions\ConflictoEdicionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReparacionRequest;
use App\Http\Requests\UpdateReparacionRequest;
use App\Models\Caja;
use App\Models\Notificacion;
use App\Models\Producto;
use App\Models\Reparacion;
use App\Services\NotificacionService;
use App\Services\ReparacionService;
use App\Support\FiltroPeriodo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Controlador de Reparaciones.
 *
 * Versión simplificada: sin búsqueda de clientes.
 * Nombre y teléfono van directo en el formulario.
 */
class ReparacionController extends Controller
{
    /** Relaciones que usa cada fila de la tabla (parcial reparaciones/partials/fila). */
    private const RELACIONES_FILA = ['caja', 'abonos', 'repuestos.producto', 'ultimoWhatsapp'];

    /**
     * Reparaciones por página: la lista y con búsqueda. La búsqueda también va por páginas:
     * en vivo, las primeras letras traen cientos (con 3.000 reparaciones, "sam" en
     * Todos / Este año eran 706 filas y 4 MB por búsqueda, y la pantalla se trababa).
     */
    private const POR_PAGINA          = 10;
    private const POR_PAGINA_BUSCANDO = 25;

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
        // Lo que llega en la URL se revisa aquí una sola vez: la consulta y la vista (select,
        // fechas, texto de la lista) usan estos mismos valores y nunca dicen cosas distintas.

        // Estado: «todos» o uno de EstadoReparacion. Cualquier otra cosa: «en_proceso»
        $estado = $request->input('estado');
        $estado = in_array($estado, ['todos', ...array_column(EstadoReparacion::cases(), 'value')], true)
            ? $estado
            : 'en_proceso';

        // Periodo y fechas, revisados (desconocido → «mes», fechas al revés → invertidas...)
        ['periodo' => $periodo, 'desde' => $desde, 'hasta' => $hasta] = FiltroPeriodo::delPedido($request);

        // Lo escrito en el buscador. Se busca desde 2 letras, contadas como en el JS (una «ñ»
        // es una letra): con una sola, que llega al cambiar un select, no hay búsqueda
        $buscar   = is_string($request->input('buscar')) ? $request->input('buscar') : null;
        $buscando = mb_strlen($buscar ?? '') >= 2;

        // Solo en_proceso no requiere filtro de fechas
        // (se quiere ver todo lo que está pendiente sin importar cuándo entró)
        $sinFiltroFecha = $estado === 'en_proceso';


        // Construcción inicial de la consulta
        $query = Reparacion::with(self::RELACIONES_FILA)

            // Filtra por estado si no es "todos"
            ->when($estado !== 'todos', fn ($q) => $q->where('estado', $estado))

            // Aplica la búsqueda únicamente si tiene al menos 2 caracteres. Palabra por
            // palabra, en nombre, teléfono, marca o modelo (ver Reparacion::scopeBuscar)
            ->when($buscando, fn ($q) => $q->buscar($buscar))

            // Ordena por fecha más reciente. El id desempata las creadas en el mismo
            // segundo: siempre en el mismo orden, así la paginación no repite ni salta ninguna
            ->latest()
            ->orderByDesc('id');

        // Filtro de fechas: el mismo cálculo que el Dashboard (ver FiltroPeriodo)
        if (! $sinFiltroFecha) {
            FiltroPeriodo::aplicar($query, $periodo, $desde, $hasta);
        }


        // Por páginas, también con búsqueda (más por página: ver POR_PAGINA_BUSCANDO).
        // withQueryString: los enlaces de página llevan la búsqueda y los filtros
        $reparaciones = $query
            ->paginate($buscando ? self::POR_PAGINA_BUSCANDO : self::POR_PAGINA)
            ->withQueryString();

        // Página que ya no existe (un enlace viejo, o la búsqueda ahora trae menos): a la
        // última que sí, con la búsqueda. Sin resultados, lastPage() es 1 y sale el aviso vacío.
        if ($reparaciones->isEmpty() && $reparaciones->currentPage() > 1) {
            return redirect()->to($reparaciones->url($reparaciones->lastPage()));
        }

        // Contadores para mostrar estadísticas
        $contadores = $this->contadores();

        // Cajas disponibles para el formulario de nueva reparación
        $cajasLibres = $this->cajasLibres();

        // Displays (pantallas activas) para el campo "¿Se usó display?"
        // de los modales de crear y editar
        $displays = $this->consultaDisplays()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'stock']);

        // Los filtros ya revisados: la vista los muestra tal cual (no lee request())
        $filtros = compact('estado', 'periodo', 'desde', 'hasta', 'buscar', 'buscando');

        // Retorna la vista principal
        // junto con toda la información necesaria
        return view('admin.reparaciones.index', compact(
            'reparaciones',
            'cajasLibres',
            'contadores',
            'displays',
            'filtros'
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
                Arr::except($request->validated(), ['original']),
                $request->user()->id,
                // Cómo estaba la reparación cuando se abrió el modal: si otra persona
                // cambió alguno de esos campos, no se guarda (se responde 409)
                $request->validated('original')
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
        } catch (ConflictoEdicionException $e) {
            return response()->json([
                'success'   => false,
                'conflicto' => true,
                'message'   => $e->getMessage(),
            ], 409);
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
     * Estado actual de algunas filas, para el tiempo real. La pantalla lo pide cuando
     * llega un aviso de Pusher o cuando vuelve después de estar sin conexión (por
     * ejemplo, la tablet en reposo).
     *
     * Devuelve el HTML de cada fila con la misma plantilla de la carga normal, los
     * contadores por estado y las cajas libres del formulario de nueva reparación.
     * Una fila que no existe vuelve como null.
     *
     * GET /admin/reparaciones/sincronizar?ids=12,15 (máximo 50)
     */
    public function sincronizar(Request $request): JsonResponse
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => trim($id))
            ->filter(fn ($id) => ctype_digit($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(50)
            ->values();

        $reparaciones = Reparacion::with(self::RELACIONES_FILA)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $filas = [];

        foreach ($ids as $id) {
            $filas[(string) $id] = isset($reparaciones[$id])
                ? view('admin.reparaciones.partials.fila', ['rep' => $reparaciones[$id]])->render()
                : null;
        }

        return response()->json([
            'filas'        => (object) $filas,
            'contadores'   => $this->contadores(),
            'cajas_libres' => $this->cajasLibres()
                ->map(fn (Caja $caja) => ['id' => $caja->id, 'nombre' => $caja->nombre_display])
                ->values(),
        ]);
    }

    /**
     * Cantidad de reparaciones por estado (tarjetas de arriba de la lista).
     */
    private function contadores(): array
    {
        $totales = Reparacion::query()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return collect(EstadoReparacion::cases())
            ->mapWithKeys(fn (EstadoReparacion $estado) => [$estado->value => (int) ($totales[$estado->value] ?? 0)])
            ->all();
    }

    /**
     * Cajas sin reparación activa, ordenadas por grupo y número.
     */
    private function cajasLibres(): Collection
    {
        return Caja::libres()
            ->orderBy('grupo')
            ->orderBy('numero')
            ->get();
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

            // Totales ya calculados: la pantalla los escribe tal cual. Si le sumara el abono
            // a lo que muestra la fila, lo contaría dos veces cuando el tiempo real ya la
            // había puesto al día.
            $reparacion->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Abono registrado correctamente.',
                'abonado' => (int) $reparacion->total_abonado,
                'saldo'   => (int) $reparacion->saldo_pendiente,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar abono: ' . $e->getMessage(),
            ], 500);
        }
    }
}
