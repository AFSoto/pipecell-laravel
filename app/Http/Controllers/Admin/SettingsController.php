<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TipoProducto;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $tiposProducto = TipoProducto::withCount('productos')->orderBy('nombre')->get();

        return view('admin.settings.index', compact('tiposProducto'));
    }

    public function storeTipoProducto(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:tipo_productos,nombre'],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max'      => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique'   => 'Ya existe un tipo con ese nombre.',
        ]);

        TipoProducto::create($data);

        return redirect()->route('admin.settings.index')
            ->with('success', "Tipo \"{$data['nombre']}\" creado con éxito.");
    }

    public function updateTipoProducto(Request $request, TipoProducto $tipoProducto)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100', "unique:tipo_productos,nombre,{$tipoProducto->id}"],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max'      => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique'   => 'Ya existe un tipo con ese nombre.',
        ]);

        $tipoProducto->update($data);

        return redirect()->route('admin.settings.index')
            ->with('success', "Tipo actualizado a \"{$data['nombre']}\".");
    }

    public function destroyTipoProducto(TipoProducto $tipoProducto)
    {
        if ($tipoProducto->productos()->exists()) {
            return redirect()->route('admin.settings.index')
                ->with('error', "No se puede eliminar \"{$tipoProducto->nombre}\" porque tiene productos asociados.");
        }

        $nombre = $tipoProducto->nombre;
        $tipoProducto->delete();

        return redirect()->route('admin.settings.index')
            ->with('success', "Tipo \"{$nombre}\" eliminado.");
    }
}
