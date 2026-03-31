<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCajaRequest;
use App\Models\Caja;

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

        return redirect()
            ->route('admin.reparaciones.index')
            ->with('success', 'Caja ' . strtoupper($data['grupo']) . $data['numero'] . ' creada correctamente.');
    }
}
