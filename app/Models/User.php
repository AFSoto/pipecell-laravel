<?php

namespace App\Models;

use App\Enums\RolUsuario;
use App\Mail\ResetPasswordMail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Support\Facades\Mail;

/**
 * Modelo de usuario del sistema.
 *
 * Extiende de Authenticatable (no de Model) porque este modelo
 * se usa para el login. Laravel necesita esto para manejar
 * la autenticación (sesiones, password hash, etc).
 */
class User extends Authenticatable implements CanResetPasswordContract
{
    use HasFactory, CanResetPassword;

    /**
     * Campos que se pueden llenar masivamente.
     *
     * Cuando haces User::create([...]), Laravel solo permite
     * guardar los campos que estén en este array.
     * Esto protege contra inyección de datos no deseados.
     */
    protected $fillable = [
        'nombre',
        'email',
        'password',
        'role',
        'estado',
        'last_login_at',
    ];

    /**
     * Campos ocultos en las respuestas JSON.
     * Si algún día haces una API, el password no se expone.
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Casts: transforman el valor de la BD a un tipo de PHP.
     *
     * 'password' => 'hashed' — Laravel hashea automáticamente al guardar
     * 'role' => RolUsuario::class — convierte el string 'admin' al Enum RolUsuario::Admin
     * 'last_login_at' => 'datetime' — convierte el string a objeto Carbon (fechas)
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => RolUsuario::class,
            'last_login_at' => 'datetime',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        Mail::to($this->email)->send(new ResetPasswordMail($token, $this->email));
    }

    // ── Relaciones ──

    /**
     * Un técnico puede tener muchas reparaciones asignadas.
     * Ejemplo: $usuario->reparaciones → colección de reparaciones
     */
    public function reparaciones(): HasMany
    {
        return $this->hasMany(Reparacion::class, 'tecnico_id');
    }

    // ── Helpers (métodos de ayuda) ──

    /**
     * Verifica si el usuario es administrador.
     * Ejemplo: if ($user->esAdmin()) { ... }
     */
    public function esAdmin(): bool
    {
        return $this->role === RolUsuario::Admin;
    }

    /**
     * Verifica si la cuenta está activa.
     * Ejemplo: if ($user->estaActivo()) { ... }
     */
    public function estaActivo(): bool
    {
        return $this->estado === 'activo';
    }
}
