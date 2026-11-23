<?php

namespace App\Models;

use App\Enums\EstadoReparacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

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
        'tiene_whatsapp',
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
            'tiene_whatsapp' => 'boolean',
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

    /**
     * Repuestos del inventario usados (hoy la interfaz maneja solo el display).
     * Ejemplo: $reparacion->repuestos->first()?->producto->nombre
     */
    public function repuestos(): HasMany
    {
        return $this->hasMany(ReparacionRepuesto::class);
    }

    /**
     * Historial de avisos al cliente (WhatsApp, y más adelante SMS).
     */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class);
    }

    /**
     * El último intento de aviso por WhatsApp, para el indicador de la fila.
     */
    public function ultimoWhatsapp(): HasOne
    {
        return $this->hasOne(Notificacion::class)->ofMany(
            ['id' => 'max'],
            fn ($query) => $query->where('canal', Notificacion::CANAL_WHATSAPP)
        );
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

    /**
     * El teléfono sin espacios, guiones, puntos, paréntesis ni "+", en SQL. replace()
     * anidado funciona igual en MySQL y en SQLite (los tests). Lo usa scopeBuscar().
     */
    private const TELEFONO_SOLO_DIGITOS = "replace(replace(replace(replace(replace(replace(telefono_cliente, ' ', ''), '-', ''), '.', ''), '(', ''), ')', ''), '+', '')";

    /**
     * Búsqueda de la lista: cada palabra tiene que estar en el nombre, el teléfono, la marca
     * o el modelo, en cualquier orden ("Samsung A10", "Ruiz Pedro"). Se separa por espacios
     * comunes, como en Productos (sin /u: un texto mal codificado no da error).
     *
     * Una palabra que es un número (sin contar paréntesis, puntos, "+" ni guiones) también
     * se compara con los dígitos del teléfono, que se guarda tal como se escribió
     * ("310 123 4567"): así "3101234567" o "310-123-4567" lo encuentran. Y como las demás,
     * busca en el modelo: "14" encuentra el "iPhone 14".
     */
    public function scopeBuscar($query, string $texto)
    {
        foreach (preg_split('/\s+/', trim($texto), -1, PREG_SPLIT_NO_EMPTY) as $palabra) {
            $query->where(function ($sub) use ($palabra) {
                $sub->where('nombre_cliente', 'like', "%{$palabra}%")
                    ->orWhere('telefono_cliente', 'like', "%{$palabra}%")
                    ->orWhere('marca', 'like', "%{$palabra}%")
                    ->orWhere('modelo', 'like', "%{$palabra}%");

                $digitos = preg_replace('/[().+-]/', '', $palabra);

                if ($digitos !== '' && ctype_digit($digitos)) {
                    $sub->orWhereRaw(self::TELEFONO_SOLO_DIGITOS . ' like ?', ["%{$digitos}%"]);
                }
            });
        }

        return $query;
    }

    // ── Helpers ──

    public function estaPagada(): bool
    {
        return $this->saldo_pendiente <= 0;
    }

    /**
     * Descripción recortada para la celda de la tabla (null si no tiene).
     * La usan la vista y la respuesta de edición, para que el recorte sea siempre el mismo.
     */
    public function descripcionCorta(): ?string
    {
        return filled($this->descripcion_falla) ? Str::limit($this->descripcion_falla, 20) : null;
    }

    /**
     * Campos que se editan en el modal "Editar reparación". Si otra persona cambia
     * alguno mientras alguien tiene el modal abierto, guardar se rechaza (conflicto).
     * El estado y los abonos no están: se manejan por separado y no se pisan.
     */
    public const CAMPOS_EDITABLES = [
        'nombre_cliente', 'telefono_cliente', 'tiene_whatsapp', 'marca', 'modelo',
        'descripcion_falla', 'valor_total', 'costo_repuestos', 'display_id', 'display_cantidad',
    ];

    /**
     * Datos de la fila para el modal de edición (atributo data-edit).
     * Necesita cargadas las relaciones repuestos.producto y abonos.
     */
    public function datosEdicion(): array
    {
        $display = $this->repuestos->first();

        return [
            'id'                => $this->id,
            'nombre_cliente'    => $this->nombre_cliente,
            'telefono_cliente'  => $this->telefono_cliente ?? '',
            'tiene_whatsapp'    => (bool) $this->tiene_whatsapp,
            'marca'             => $this->marca,
            'modelo'            => $this->modelo ?? '',
            'descripcion_falla' => $this->descripcion_falla ?? '',
            'valor_total'       => (int) $this->valor_total,
            'costo_repuestos'   => (int) $this->costo_repuestos,
            'display_id'        => $display?->producto_id,
            'display_nombre'    => $display?->producto?->nombre,
            'display_cantidad'  => $display?->cantidad ?? 1,
            'abonos'            => $this->abonos->map(fn ($abono) => [
                'monto' => (int) $abono->monto,
                'nota'  => $abono->nota ?? '',
                'fecha' => $abono->created_at->format('d/m/Y h:i A'),
            ])->values()->all(),
        ];
    }

    /**
     * Los campos editables como texto, para comparar dos versiones de la reparación
     * (vacío y null cuentan igual; true/false pasan a "1"/"0"). Solo incluye los
     * campos que vienen en $datos.
     */
    public static function huellaEdicion(array $datos): array
    {
        return collect(self::CAMPOS_EDITABLES)
            ->filter(fn (string $campo) => array_key_exists($campo, $datos))
            ->mapWithKeys(fn (string $campo) => [
                $campo => is_bool($datos[$campo])
                    ? ($datos[$campo] ? '1' : '0')
                    : trim((string) ($datos[$campo] ?? '')),
            ])
            ->all();
    }

    /**
     * Indica si alguno de los campos editables cambió respecto de cómo estaban
     * cuando se abrió el modal ($original, tal como lo tenía la pantalla).
     */
    public function cambioDesde(array $original): bool
    {
        $abierto = self::huellaEdicion($original);
        $actual  = self::huellaEdicion($this->datosEdicion());

        foreach ($abierto as $campo => $valor) {
            if ($valor !== $actual[$campo]) {
                return true;
            }
        }

        return false;
    }
}
