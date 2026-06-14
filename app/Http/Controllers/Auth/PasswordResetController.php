<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * Muestra el formulario para solicitar el correo de recuperación.
     */
    public function showForgotForm(): View
    {
        return view('auth.olvide-contrasena');
    }

    /**
     * Envía el correo con el enlace de recuperación.
     *
     * Usa el broker de Laravel (Password::sendResetLink) que:
     * 1. Verifica que el email exista en la BD
     * 2. Genera un token seguro y lo guarda hasheado en password_reset_tokens
     * 3. Llama a sendPasswordResetNotification() del User (nuestro Mailable)
     *
     * Siempre responde con el mismo mensaje de éxito para no revelar
     * si el email está registrado o no (seguridad).
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email'    => 'Ingresa un correo electrónico válido.',
        ]);

        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'Si ese correo está registrado, recibirás un enlace en los próximos minutos.');
    }

    /**
     * Muestra el formulario para ingresar la nueva contraseña.
     *
     * El token llega firmado en la URL. Si alguien intenta
     * llegar aquí sin un token válido, el formulario no procesa nada.
     */
    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.restablecer-contrasena', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Aplica el restablecimiento de contraseña.
     *
     * Password::reset() valida que:
     * - El token no haya expirado (60 min por defecto, config/auth.php)
     * - El token corresponda al email enviado
     * - El token no haya sido usado antes
     *
     * Si todo es correcto, ejecuta el closure, actualiza la contraseña
     * y elimina el token (un uso único).
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'token.required'        => 'El token de recuperación es inválido.',
            'email.required'        => 'El correo electrónico es obligatorio.',
            'email.email'           => 'Ingresa un correo electrónico válido.',
            'password.required'     => 'La nueva contraseña es obligatoria.',
            'password.confirmed'    => 'Las contraseñas no coinciden.',
            'password.min'          => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', '¡Contraseña restablecida con éxito! Ya puedes iniciar sesión.');
        }

        throw ValidationException::withMessages([
            'email' => __($status),
        ]);
    }
}
