<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReparacionRequest;
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
     * Lista todas las reparaciones.
     */
    public function index()
    {
        $reparaciones = Reparacion::with(['caja', 'abonos'])
            ->latest()
            ->get();

        $cajasLibres = Caja::libres()
            ->orderBy('grupo')
            ->orderBy('numero')
            ->get();

        return view('admin.reparaciones.index', compact('reparaciones', 'cajasLibres'));
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
