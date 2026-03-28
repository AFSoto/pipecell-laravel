<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReparacionRequest;
use App\Http\Requests\UpdateReparacionRequest;
use App\Models\Caja;
use App\Models\Reparacion;
use App\Services\ReparacionService;
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
        private ReparacionService $reparacionService
    ) {}

    /**
     * Lista las reparaciones con filtros de estado, periodo y búsqueda.
     */
    public function index(Request $request)
    {
        $estado  = $request->input('estado');
        $periodo = $request->input('periodo', 'mes');
        $buscar  = $request->input('buscar');
        $desde   = $request->input('desde');
        $hasta   = $request->input('hasta');

        // en_proceso y arreglado siempre tienen pocos registros — sin filtro de fecha
        $sinFiltroFecha = in_array($estado, ['en_proceso', 'arreglado']);

        $query = Reparacion::with(['caja', 'abonos'])
            ->when($estado && $estado !== 'todos', fn($q) => $q->where('estado', $estado))
            ->when(! $sinFiltroFecha, function ($q) use ($periodo, $desde, $hasta) {
                match ($periodo) {
                    'hoy'          => $q->whereDate('created_at', today()),
                    'semana'       => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                    'mes_pasado'   => $q->whereMonth('created_at', now()->subMonth()->month)
                        ->whereYear('created_at', now()->subMonth()->year),
                    'trimestre'    => $q->whereBetween('created_at', [now()->subMonths(3)->startOfDay(), now()->endOfDay()]),
                    'anio'         => $q->whereYear('created_at', now()->year),
                    'personalizado' => ($desde && $hasta)
                        ? $q->whereBetween('created_at', [$desde, $hasta . ' 23:59:59'])
                        : $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
                    default        => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
                };
            })
            ->when(strlen($buscar ?? '') >= 2, function ($q) use ($buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('nombre_cliente', 'like', "%{$buscar}%")
                        ->orWhere('telefono_cliente', 'like', "%{$buscar}%")
                        ->orWhere('marca', 'like', "%{$buscar}%")
                        ->orWhere('modelo', 'like', "%{$buscar}%");
                });
            })
            ->latest();

        $reparaciones = strlen($buscar ?? '') >= 2 ? $query->get() : $query->paginate(10);

        $contadores = [
            'en_proceso' => Reparacion::where('estado', 'en_proceso')->count(),
            'arreglado'  => Reparacion::where('estado', 'arreglado')->count(),
            'entregado'  => Reparacion::where('estado', 'entregado')->count(),
        ];

        $cajasLibres = Caja::libres()
            ->orderBy('grupo')
            ->orderBy('numero')
            ->get();

        return view('admin.reparaciones.index', compact('reparaciones', 'cajasLibres', 'contadores'));
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
            $reparacion->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Reparación #{$reparacion->id} actualizada con éxito.",
                'data'    => [
                    'id'                => $reparacion->id,
                    'nombre_cliente'    => $reparacion->nombre_cliente,
                    'telefono_cliente'  => $reparacion->telefono_cliente,
                    'marca'             => $reparacion->marca,
                    'modelo'            => $reparacion->modelo,
                    'descripcion_falla' => $reparacion->descripcion_falla,
                    'valor_total'       => (int) $reparacion->valor_total,
                    'costo_repuestos'   => (int) $reparacion->costo_repuestos,
                ],
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
            $this->reparacionService->crear($request->validated());

            return redirect()
                ->route('admin.reparaciones.index')
                ->with('success', 'Reparación registrada con éxito.');
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

        try {
            $this->reparacionService->actualizarEstado(
                $reparacion,
                $request->estado
            );

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Registra un abono via AJAX.
     */
    public function registrarAbono(Request $request, Reparacion $reparacion)
    {
        $request->validate([
            'monto' => ['required', 'numeric', 'min:1'],
            'nota'  => ['nullable', 'string', 'max:255'],
        ]);

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
