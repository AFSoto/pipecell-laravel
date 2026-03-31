<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdatePerfilRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerfilController extends Controller
{
    /**
     * Muestra la página de perfil con las tres secciones:
     * información personal, cambio de contraseña y sesiones activas.
     */
    public function index()
    {
        $sesiones = $this->obtenerSesiones();

        return view('admin.perfil.index', compact('sesiones'));
    }

    /**
     * Actualiza el nombre y email del usuario autenticado.
     */
    public function actualizarInfo(UpdatePerfilRequest $request)
    {
        auth()->user()->update($request->validated());

        return redirect()
            ->route('admin.perfil')
            ->with('success_perfil', 'Información actualizada correctamente.');
    }

    /**
     * Cambia la contraseña del usuario autenticado.
     * La verificación de la contraseña actual la hace UpdatePasswordRequest
     * mediante la regla 'current_password'.
     */
    public function cambiarPassword(UpdatePasswordRequest $request)
    {
        auth()->user()->update([
            'password' => $request->nueva_password,
            // El cast 'hashed' del modelo lo hashea automáticamente al guardar
        ]);

        return redirect()
            ->route('admin.perfil')
            ->with('success_password', 'Contraseña actualizada correctamente.');
    }

    /**
     * Cierra todas las sesiones del usuario autenticado excepto la actual.
     * Solo aplica cuando el driver de sesiones es 'database'.
     */
    public function cerrarOtrasSesiones(Request $request)
    {
        try {
            DB::table('sessions')
                ->where('user_id', auth()->id())
                ->where('id', '!=', session()->getId())
                ->delete();

            return redirect()
                ->route('admin.perfil')
                ->with('success_sesiones', 'Todas las otras sesiones han sido cerradas.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.perfil')
                ->with('error', 'No se pudieron cerrar las sesiones.');
        }
    }

    /**
     * Obtiene las sesiones activas del usuario autenticado desde la tabla sessions.
     * Parsea el user_agent para mostrar información legible de navegador y SO.
     * Retorna array vacío si la tabla no existe o el driver no es database.
     */
    private function obtenerSesiones(): array
    {
        try {
            return DB::table('sessions')
                ->where('user_id', auth()->id())
                ->orderByDesc('last_activity')
                ->get()
                ->map(function ($sesion) {
                    $ua     = $sesion->user_agent ?? '';
                    $parsed = $this->parsearUserAgent($ua);

                    return [
                        'id'             => $sesion->id,
                        'es_actual'      => $sesion->id === session()->getId(),
                        'ip'             => $sesion->ip_address ?? '—',
                        'navegador'      => $parsed['navegador'],
                        'sistema'        => $parsed['sistema'],
                        'ultima_actividad' => \Carbon\Carbon::createFromTimestamp($sesion->last_activity),
                    ];
                })
                ->toArray();
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Extrae navegador y sistema operativo del user agent string.
     * Detección básica sin librerías externas.
     */
    private function parsearUserAgent(string $ua): array
    {
        // Navegador
        $navegador = match (true) {
            str_contains($ua, 'Edg')                                => 'Edge',
            str_contains($ua, 'OPR') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Chrome')                             => 'Chrome',
            str_contains($ua, 'Firefox')                            => 'Firefox',
            str_contains($ua, 'Safari')                             => 'Safari',
            default                                                  => 'Navegador desconocido',
        };

        // Sistema operativo
        $sistema = match (true) {
            str_contains($ua, 'Windows')                                    => 'Windows',
            str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS')  => 'macOS',
            str_contains($ua, 'iPhone')                                     => 'iPhone',
            str_contains($ua, 'iPad')                                       => 'iPad',
            str_contains($ua, 'Android')                                    => 'Android',
            str_contains($ua, 'Linux')                                      => 'Linux',
            default                                                          => 'SO desconocido',
        };

        return ['navegador' => $navegador, 'sistema' => $sistema];
    }
}
