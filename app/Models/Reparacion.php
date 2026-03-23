<?php

namespace App\Models;

use App\Enums\EstadoReparacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Modelo principal de reparación.
 *
 * Representa un celular que entró al local para ser reparado.
 * Se relaciona con el cliente que lo trajo, la caja donde se guardó,
 * y los abonos (pagos) que ha recibido.
 */
class Reparacion extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla en la BD.
     *
     * Laravel por defecto buscaría "reparacions" (le agrega "s" al nombre).
     * Como nuestra tabla se llama "reparaciones", se lo indicamos manualmente.
     */
    protected $table = 'reparaciones';

    protected $fillable = [
        'cliente_id',
        'tecnico_id',
        'caja_id',
        'marca',
        'modelo',
        'descripcion_falla',
        'valor_total',
        'costo_repuestos',
        'estado',
        'fecha_entrega',
    ];

    /**
     * Casts para transformar datos de la BD.
     *
     * 'estado' → convierte 'en_proceso' al Enum EstadoReparacion::EnProceso
     * 'decimal:2' → asegura que siempre tenga 2 decimales (ej: 50000.00)
     * 'datetime' → convierte el string a objeto Carbon para manejar fechas fácil
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoReparacion::class,
            'valor_total' => 'decimal:2',
            'costo_repuestos' => 'decimal:2',
            'fecha_entrega' => 'datetime',
        ];
    }

    // ── Relaciones ──

    /**
     * El cliente que trajo el celular.
     * Ejemplo: $reparacion->cliente->nombre → "Juan Pérez"
     * Ejemplo: $reparacion->cliente->telefono → "3101234567"
     *
     * BelongsTo = "esta reparación pertenece a un cliente"
     * Es lo contrario de HasMany en el modelo Cliente.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * El técnico que hizo la reparación (opcional por ahora).
     *
     * El segundo parámetro 'tecnico_id' le dice a Laravel
     * qué columna usar, porque por defecto buscaría 'user_id'.
     */
    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    /**
     * La caja donde se guardó el celular.
     * Ejemplo: $reparacion->caja->nombre_display → "A1"
     */
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    /**
     * Los abonos (pagos) que ha recibido esta reparación.
     * Ejemplo: $reparacion->abonos → colección de pagos
     */
    public function abonos(): HasMany
    {
        return $this->hasMany(Abono::class);
    }

    // ── Accessors (campos calculados, no se guardan en la BD) ──

    /**
     * Suma de todos los abonos de esta reparación.
     * Ejemplo: $reparacion->total_abonado → 80000.00
     */
    protected function totalAbonado(): Attribute
    {
        return Attribute::get(fn () => $this->abonos()->sum('monto'));
    }

    /**
     * Lo que falta por pagar: valor_total - total_abonado.
     * Ejemplo: $reparacion->saldo_pendiente → 20000.00
     */
    protected function saldoPendiente(): Attribute
    {
        return Attribute::get(fn () => $this->valor_total - $this->total_abonado);
    }

    /**
     * Ganancia: valor_total - costo_repuestos.
     * Ejemplo: $reparacion->ganancia → 35000.00
     * Útil para el dashboard.
     */
    protected function ganancia(): Attribute
    {
        return Attribute::get(fn () => $this->valor_total - ($this->costo_repuestos ?? 0));
    }

    // ── Scopes (filtros reutilizables) ──

    /**
     * Filtra reparaciones en proceso.
     * Uso: Reparacion::enProceso()->get()
     *
     * Esto reemplaza tu método getEnProceso() del proyecto anterior.
     * La ventaja es que puedes encadenar:
     * Reparacion::enProceso()->where('marca', 'Samsung')->get()
     */
    public function scopeEnProceso($query)
    {
        return $query->where('estado', EstadoReparacion::EnProceso);
    }

    /**
     * Filtra reparaciones arregladas.
     * Uso: Reparacion::arregladas()->get()
     */
    public function scopeArregladas($query)
    {
        return $query->where('estado', EstadoReparacion::Arreglado);
    }

    /**
     * Filtra reparaciones entregadas.
     * Uso: Reparacion::entregadas()->get()
     */
    public function scopeEntregadas($query)
    {
        return $query->where('estado', EstadoReparacion::Entregado);
    }

    /**
     * Filtra reparaciones creadas hoy.
     * Uso: Reparacion::deHoy()->get()
     * Útil para el dashboard: "reparaciones de hoy"
     */
    public function scopeDeHoy($query)
    {
        return $query->whereDate('created_at', today());
    }

    // ── Helpers ──

    /**
     * Verifica si la reparación ya está pagada completamente.
     * Ejemplo: if ($reparacion->estaPagada()) { ... }
     */
    public function estaPagada(): bool
    {
        return $this->saldo_pendiente <= 0;
    }
}
