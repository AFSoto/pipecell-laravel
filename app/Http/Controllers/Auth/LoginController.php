<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador de autenticación (Login / Logout).
 *
 * Maneja el inicio y cierre de sesión de los usuarios.
 * Solo los usuarios con estado 'activo' pueden entrar.
 *
 * Auth es el facade de Laravel para manejar autenticación.
 * Auth::attempt() verifica email + password contra la BD.
 * Auth::logout() cierra la sesión del usuario.
 */
class LoginController extends Controller
{
    /**
     * Muestra el formulario de login.
     *
     * Si el usuario ya está logueado, lo redirige al panel admin
     * para que no vea el formulario de nuevo.
     */
    public function showLogin()
    {
        // Si ya está autenticado, mandarlo al admin
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    /**
     * Procesa el intento de login.
     *
     * 1. Valida que email y password vengan llenos
     * 2. Intenta autenticar con Auth::attempt()
     * 3. Si las credenciales son correctas, verifica que el usuario esté activo
     * 4. Si todo está bien, regenera la sesión (seguridad) y redirige al admin
     * 5. Si falla, regresa al formulario con un mensaje de error
     */
    public function login(Request $request)
    {
        // Validación básica — los campos son obligatorios
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Auth::attempt() busca el email en la BD y compara el password hasheado
        if (Auth::attempt($credentials)) {

            // Verificar que el usuario esté activo
            if (Auth::user()->estado !== 'activo') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Tu cuenta está desactivada. Contacta al administrador.',
                ]);
            }

            // Actualizar la fecha de último login
            Auth::user()->update(['last_login_at' => now()]);

            // Regenerar sesión para prevenir ataques de fijación de sesión
            $request->session()->regenerate();

            // Redirigir al panel admin
            return redirect()->intended(route('admin.dashboard'));
        }

        // Si las credenciales son incorrectas, regresa con error
        // back() regresa al formulario anterior
        // withErrors() manda el mensaje de error a la vista
        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email'); // Mantiene el email escrito para no tener que reescribirlo
    }

    /**
     * Cierra la sesión del usuario.
     *
     * 1. Cierra la sesión con Auth::logout()
     * 2. Invalida la sesión actual (seguridad)
     * 3. Regenera el token CSRF (seguridad)
     * 4. Redirige a la landing page
     */
    public function logout(Request $request)
    {
        Auth::logout();

        // Invalidar la sesión y regenerar token por seguridad
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}
