<?php

namespace App\Services;

use App\Enums\EstadoNotificacion;
use App\Events\ReparacionCambio;
use App\Exceptions\WhatsAppException;
use App\Models\Notificacion;
use App\Models\Reparacion;
use App\Support\TiempoReal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
     * Nunca manda dos veces: si ya hay un aviso enviado, no hace nada. Todo pasa con
     * la fila de la reparación bloqueada, así dos peticiones al mismo tiempo (dos
     * personas, o el cambio de estado y el botón) no mandan dos mensajes: la segunda
     * espera a que termine la primera y encuentra el aviso enviado. El bloqueo dura
     * lo que tarde Meta en responder (máximo 10 s).
     *
     * Con $avisar en false (quien la pasó a «Arreglado» quitó la opción del modal, por
     * ejemplo porque el cliente estaba en el taller) no se llama a Meta: queda «omitido»
     * con ese motivo, y el botón de la fila lo puede enviar después.
     *
     * @return Notificacion|null  null si ya se había avisado
     */
    public function notificarReparacionLista(Reparacion $reparacion, ?int $userId = null, bool $avisar = true): ?Notificacion
    {
        return DB::transaction(function () use ($reparacion, $userId, $avisar) {
            $reparacion = Reparacion::lockForUpdate()->findOrFail($reparacion->id);

            if ($this->yaSeAviso($reparacion)) {
                return null;
            }

            if (! $avisar) {
                return $this->registrar($this->datosDelIntento($reparacion, $userId), EstadoNotificacion::Omitido, Notificacion::DETALLE_NO_AVISAR);
            }

            return $this->intentarEnvio($reparacion, $userId);
        });
    }

    /**
     * Por qué no se le puede avisar al cliente por WhatsApp, o null si sí se puede. Es la
     * única regla: la usan el envío (el motivo queda en el «omitido») y la fila, que solo
     * pregunta si se avisa cuando de verdad se enviaría (celularParaAviso()).
     */
    public function motivoSinAviso(Reparacion $reparacion): ?string
    {
        return match (true) {
            blank($reparacion->telefono_cliente)                             => 'el cliente no tiene teléfono registrado.',
            ! $reparacion->tiene_whatsapp                                    => 'el cliente no tiene WhatsApp.',
            self::normalizarTelefono($reparacion->telefono_cliente) === null => 'el teléfono no es un celular colombiano válido.',
            ! $this->whatsApp->estaConfigurado()                             => 'WhatsApp no está configurado en la plataforma.',
            default                                                          => null,
        };
    }

    /**
     * El celular al que se le avisaría al pasar a «Arreglado» ("310 123 4567"), o null si no
     * se le avisaría: hay un motivo (motivoSinAviso) o el último aviso ya salió, y nunca se
     * manda dos veces. Lo muestra el modal que pregunta si se avisa (data-avisar-a de la fila).
     *
     * Sale de los dígitos ya revisados, no del teléfono como se escribió: solo trae números y
     * espacios. Usa ultimoWhatsapp, que la lista ya trae cargado.
     */
    public function celularParaAviso(Reparacion $reparacion): ?string
    {
        if ($this->motivoSinAviso($reparacion) !== null
            || $reparacion->ultimoWhatsapp?->estado === EstadoNotificacion::Enviado) {
            return null;
        }

        $celular = substr(self::normalizarTelefono($reparacion->telefono_cliente), 2);   // sin el 57

        return substr($celular, 0, 3) . ' ' . substr($celular, 3, 3) . ' ' . substr($celular, 6);
    }

    /** Lo que tiene todo intento de aviso de «reparación lista». */
    private function datosDelIntento(Reparacion $reparacion, ?int $userId): array
    {
        return [
            'reparacion_id' => $reparacion->id,
            'user_id'       => $userId,
            'canal'         => Notificacion::CANAL_WHATSAPP,
            'tipo'          => Notificacion::TIPO_REPARACION_LISTA,
            'telefono'      => $reparacion->telefono_cliente,
        ];
    }

    /**
     * Un intento de envío: queda registrado como enviado, fallido u omitido.
     */
    private function intentarEnvio(Reparacion $reparacion, ?int $userId): Notificacion
    {
        $base = $this->datosDelIntento($reparacion, $userId);

        if (($motivo = $this->motivoSinAviso($reparacion)) !== null) {
            return $this->registrar($base, EstadoNotificacion::Omitido, $motivo);
        }

        $telefono = self::normalizarTelefono($reparacion->telefono_cliente);

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
        } catch (\Throwable $e) {
            // Un error que no viene de Meta (un fallo inesperado de la plataforma o de
            // la conexión): queda como fallido para que la fila lo muestre y se pueda
            // reintentar, y el detalle técnico va a laravel.log.
            report($e);

            return $this->registrar($base, EstadoNotificacion::Fallido, Notificacion::DETALLE_ERROR_INTERNO);
        }
    }

    /**
     * Aplica un estado que Meta informa por el webhook a la notificación de ese mensaje:
     *   delivered → entregado_at
     *   read      → leido_at (y entregado_at si faltaba)
     *   failed    → pasa a "fallido" con el motivo, así la fila muestra "Reintentar"
     *
     * Nunca retrocede: Meta puede mandar los estados en desorden (un "read" antes
     * del "delivered"), y un "failed" que llega después de entregado no lo deshace.
     * Los mensajes que no son de la plataforma se ignoran.
     *
     * @param  array  $estado  Un elemento de entry[].changes[].value.statuses[]
     */
    public function registrarEstadoDeEntrega(array $estado): void
    {
        $mensajeId    = $estado['id'] ?? null;
        $notificacion = $mensajeId ? Notificacion::where('mensaje_id', $mensajeId)->first() : null;

        if (! $notificacion || $notificacion->estado !== EstadoNotificacion::Enviado) {
            return;
        }

        $momento = isset($estado['timestamp']) && is_numeric($estado['timestamp'])
            ? Carbon::createFromTimestamp((int) $estado['timestamp'], config('app.timezone'))
            : now();

        switch ($estado['status'] ?? null) {
            case 'delivered':
                $notificacion->entregado_at ??= $momento;
                break;

            case 'read':
                $notificacion->entregado_at ??= $momento;
                $notificacion->leido_at     ??= $momento;
                break;

            case 'failed':
                if ($notificacion->entregado_at === null) {
                    $notificacion->estado  = EstadoNotificacion::Fallido;
                    $notificacion->detalle = Str::limit('Meta no lo pudo entregar: ' . $this->motivoDeFalla($estado), 490);
                }
                break;
        }

        if (! $notificacion->isDirty()) {
            return;
        }

        $notificacion->save();

        // Las pantallas abiertas cambian el indicador sin recargar (y avisan si no llegó).
        // Lo informa Meta, no una persona: sale sin autor.
        TiempoReal::avisar(new ReparacionCambio($notificacion->reparacion_id, ReparacionCambio::WHATSAPP, delSistema: true));
    }

    /**
     * Motivo legible de un "failed" del webhook: el texto conocido para el código
     * o, si no lo hay, el detalle que manda Meta.
     */
    private function motivoDeFalla(array $estado): string
    {
        $error = $estado['errors'][0] ?? [];

        return WhatsAppService::mensajePorCodigo($error['code'] ?? null)
            ?? $error['error_data']['details']
            ?? $error['message']
            ?? $error['title']
            ?? 'Meta no indicó el motivo.';
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
     * Valores de la plantilla "reparacion_lista", en orden: {{1}} nombre del cliente y
     * {{2}} equipo (marca + modelo). El mensaje no dice el saldo (decisión del dueño).
     * La plantilla aprobada en Meta tiene que tener estas 2 variables: si no, Meta rechaza
     * el envío (error 132000, que la fila muestra en español).
     */
    private function parametrosPlantilla(Reparacion $reparacion): array
    {
        $equipo = trim($reparacion->marca . ' ' . ($reparacion->modelo ?? ''));

        return [$reparacion->nombre_cliente, $equipo];
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
        $notificacion = Notificacion::create($datos + [
            'estado'  => $estado,
            'detalle' => $detalle !== null ? Str::limit($detalle, 490) : null,
        ]);

        TiempoReal::avisar(new ReparacionCambio($notificacion->reparacion_id, ReparacionCambio::WHATSAPP));

        return $notificacion;
    }
}
