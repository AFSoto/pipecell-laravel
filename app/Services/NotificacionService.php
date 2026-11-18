<?php

namespace App\Services;

use App\Enums\EstadoNotificacion;
use App\Exceptions\WhatsAppException;
use App\Models\Notificacion;
use App\Models\Reparacion;
use Illuminate\Support\Str;

/**
 * Avisos al cliente sobre su reparación.
 *
 * Hoy solo WhatsApp. Cada intento queda registrado en la tabla notificaciones
 * (enviado, fallido u omitido con su motivo) para mostrarlo en la plataforma.
 * Para agregar SMS: un SmsService con el mismo contrato y canal 'sms'.
 *
 * El envío es síncrono a propósito: el servidor no tiene worker de colas,
 * igual que el correo de recuperación de contraseña.
 */
class NotificacionService
{
    public function __construct(
        private WhatsAppService $whatsApp
    ) {}

    /**
     * Avisa por WhatsApp que la reparación está lista para recoger.
     *
     * @param  bool  $forzar  true desde el botón "Enviar / Reintentar WhatsApp":
     *                        envía aunque ya exista un aviso enviado.
     * @return Notificacion|null  null si ya se había avisado y no se forzó
     */
    public function notificarReparacionLista(Reparacion $reparacion, ?int $userId = null, bool $forzar = false): ?Notificacion
    {
        if (! $forzar && $this->yaSeAviso($reparacion)) {
            return null;
        }

        $base = [
            'reparacion_id' => $reparacion->id,
            'user_id'       => $userId,
            'canal'         => Notificacion::CANAL_WHATSAPP,
            'tipo'          => Notificacion::TIPO_REPARACION_LISTA,
            'telefono'      => $reparacion->telefono_cliente,
        ];

        if (blank($reparacion->telefono_cliente)) {
            return $this->registrar($base, EstadoNotificacion::Omitido, 'el cliente no tiene teléfono registrado.');
        }

        if (! $reparacion->tiene_whatsapp) {
            return $this->registrar($base, EstadoNotificacion::Omitido, 'el cliente no tiene WhatsApp.');
        }

        $telefono = self::normalizarTelefono($reparacion->telefono_cliente);

        if ($telefono === null) {
            return $this->registrar($base, EstadoNotificacion::Omitido, 'el teléfono no es un celular colombiano válido.');
        }

        if (! $this->whatsApp->estaConfigurado()) {
            return $this->registrar($base, EstadoNotificacion::Omitido, 'WhatsApp no está configurado en la plataforma.');
        }

        try {
            $mensajeId = $this->whatsApp->enviarPlantilla(
                $telefono,
                config('services.whatsapp.plantilla_lista'),
                config('services.whatsapp.idioma'),
                $this->parametrosPlantilla($reparacion)
            );

            return $this->registrar($base + ['mensaje_id' => $mensajeId], EstadoNotificacion::Enviado);
        } catch (WhatsAppException $e) {
            return $this->registrar($base, EstadoNotificacion::Fallido, $e->getMessage());
        }
    }

    /**
     * Convierte un celular colombiano al formato que exige WhatsApp (57 + 10 dígitos).
     *
     * "310 123 4567" → "573101234567", "+57 310-123-4567" → "573101234567".
     * Devuelve null para fijos, números incompletos o de otro país.
     */
    public static function normalizarTelefono(?string $telefono): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $telefono);

        if (strlen($digitos) === 10 && str_starts_with($digitos, '3')) {
            return '57' . $digitos;
        }

        if (strlen($digitos) === 12 && str_starts_with($digitos, '573')) {
            return $digitos;
        }

        return null;
    }

    /**
     * Valores de la plantilla "reparacion_lista", en orden:
     * {{1}} nombre del cliente, {{2}} equipo (marca + modelo), {{3}} saldo pendiente.
     */
    private function parametrosPlantilla(Reparacion $reparacion): array
    {
        $equipo = trim($reparacion->marca . ' ' . ($reparacion->modelo ?? ''));
        $saldo  = '$' . number_format(max(0, (float) $reparacion->saldo_pendiente), 0, ',', '.');

        return [$reparacion->nombre_cliente, $equipo, $saldo];
    }

    private function yaSeAviso(Reparacion $reparacion): bool
    {
        return $reparacion->notificaciones()
            ->where('canal', Notificacion::CANAL_WHATSAPP)
            ->where('tipo', Notificacion::TIPO_REPARACION_LISTA)
            ->where('estado', EstadoNotificacion::Enviado)
            ->exists();
    }

    private function registrar(array $datos, EstadoNotificacion $estado, ?string $detalle = null): Notificacion
    {
        return Notificacion::create($datos + [
            'estado'  => $estado,
            'detalle' => $detalle !== null ? Str::limit($detalle, 490) : null,
        ]);
    }
}
