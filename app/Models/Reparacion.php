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
 * Versión simplificada: nombre y teléfono del cliente
 * van directo en esta tabla, sin tabla de clientes separada.
 */
class Reparacion extends Model
{
    use HasFactory;

    /**
     * Nombre real de la tabla en la BD.
     * Laravel buscaría "reparacions" por defecto, así que lo corregimos.
     */
    protected $table = 'reparaciones';

    protected $fillable = [
        'nombre_cliente',
        'telefono_cliente',
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
     * El técnico que hizo la reparación (opcional por ahora).
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
     */
    public function abonos(): HasMany
    {
        return $this->hasMany(Abono::class);
    }

    // ── Accessors (campos calculados) ──

    /**
     * Suma de todos los abonos.
     * Ejemplo: $reparacion->total_abonado → 80000.00
     */
    protected function totalAbonado(): Attribute
    {
        return Attribute::get(function () {
            // Si la relación ya fue eager-loaded, usa la colección en memoria (sin query extra).
            // Si no, cae al sum() de la BD para no forzar la carga de todos los abonos.
            return $this->relationLoaded('abonos')
                ? $this->abonos->sum('monto')
                : $this->abonos()->sum('monto');
        });
    }

    /**
     * Lo que falta por pagar.
     * Ejemplo: $reparacion->saldo_pendiente → 20000.00
     */
    protected function saldoPendiente(): Attribute
    {
        return Attribute::get(fn () => $this->valor_total - $this->total_abonado);
    }

    /**
     * Ganancia: valor total - costo repuestos.
     */
    protected function ganancia(): Attribute
    {
        return Attribute::get(fn () => $this->valor_total - ($this->costo_repuestos ?? 0));
    }

    // ── Scopes ──

    public function scopeEnProceso($query)
    {
        return $query->where('estado', EstadoReparacion::EnProceso);
    }

    public function scopeArregladas($query)
    {
        return $query->where('estado', EstadoReparacion::Arreglado);
    }

    public function scopeEntregadas($query)
    {
        return $query->where('estado', EstadoReparacion::Entregado);
    }

    public function scopeDeHoy($query)
    {
        return $query->whereDate('created_at', today());
    }

    // ── Helpers ──

    public function estaPagada(): bool
    {
        return $this->saldo_pendiente <= 0;
    }
}
