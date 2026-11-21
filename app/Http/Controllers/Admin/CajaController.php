<?php

namespace App\Http\Controllers\Admin;

use App\Events\CajasCambio;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCajaRequest;
use App\Models\Caja;
use App\Support\TiempoReal;

class CajaController extends Controller
{
    public function store(StoreCajaRequest $request)
    {
        $data = $request->validated();

        Caja::create([
            'grupo'  => strtoupper($data['grupo']),
            'numero' => $data['numero'],
            // estado queda en 'libre' por el default de la migración
        ]);

        // Las otras pantallas agregan la caja nueva al formulario de nueva reparación
        TiempoReal::avisar(new CajasCambio());

        return redirect()
            ->route('admin.reparaciones.index')
            ->with('success', 'Caja ' . strtoupper($data['grupo']) . $data['numero'] . ' creada correctamente.');
    }
}
