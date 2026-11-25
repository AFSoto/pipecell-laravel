<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * Webhook de Meta: confirma si el aviso por WhatsApp llegó, se leyó o no se pudo entregar.
 * Los avisos de prueba se firman igual que los firma Meta (HMAC SHA-256 con el App Secret,
 * en X-Hub-Signature-256) o Cosend, que los reenvía en el formato de Meta firmados con el
 * signing secret del número, en X-Cosend-Signature-256.
 */
class WhatsappWebhookTest extends TestCase
{
    use RefreshDatabase;
    use CreaDatosDeReparacion;

    private const SECRETO = 'secreto-de-prueba';

    private const FIRMA_META   = 'X-Hub-Signature-256';
    private const FIRMA_COSEND = 'X-Cosend-Signature-256';

    /** 2025-11-21 11:00:00 en Colombia */
    private const MOMENTO = 1763740800;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.token'           => 'token-de-prueba',
            'services.whatsapp.phone_number_id' => '1234567890',
            'services.whatsapp.verify_token'    => 'token-verificacion',
            'services.whatsapp.app_secret'      => self::SECRETO,
        ]);

        Http::preventStrayRequests();
    }

    private User $usuario;

    /** Reparación en "Arreglado" con un aviso que Meta aceptó (wamid.PRUEBA). */
    private function avisoEnviado(): Notificacion
    {
        $this->usuario = $this->crearUsuario();
        $reparacion    = $this->crearReparacion($this->usuario);
        $reparacion->update(['estado' => 'arreglado']);

        return Notificacion::create([
            'reparacion_id' => $reparacion->id,
            'canal'         => Notificacion::CANAL_WHATSAPP,
            'tipo'          => Notificacion::TIPO_REPARACION_LISTA,
            'telefono'      => '310 123 4567',
            'estado'        => 'enviado',
            'mensaje_id'    => 'wamid.PRUEBA',
        ]);
    }

    private function estado(string $status, int $momento = self::MOMENTO, array $extra = []): array
    {
        return array_merge([
            'id'           => 'wamid.PRUEBA',
            'status'       => $status,
            'timestamp'    => (string) $momento,
            'recipient_id' => '573101234567',
        ], $extra);
    }

    /** Un cambio de Meta con esos estados: lo que va dentro de entry[].changes[]. */
    private function cambio(array $estados, string $campo = 'messages'): array
    {
        return [
            'field' => $campo,
            'value' => [
                'messaging_product' => 'whatsapp',
                'metadata'          => ['phone_number_id' => '1234567890'],
                'statuses'          => $estados,
            ],
        ];
    }

    /** El aviso completo, con el sobre que usa Meta (y que conserva Cosend al reenviarlo). */
    private function cuerpoDeMeta(array $estados, string $campo = 'messages'): string
    {
        return json_encode([
            'object' => 'whatsapp_business_account',
            'entry'  => [['id' => 'WABA_ID', 'changes' => [$this->cambio($estados, $campo)]]],
        ]);
    }

    /** Manda $cuerpo al webhook con esas firmas: [cabecera => secreto]. Sin firmas: sin firma. */
    private function enviarCuerpo(string $cuerpo, array $firmas): TestResponse
    {
        $servidor = ['CONTENT_TYPE' => 'application/json'];

        foreach ($firmas as $cabecera => $secreto) {
            $servidor['HTTP_' . strtoupper(str_replace('-', '_', $cabecera))] = 'sha256=' . hash_hmac('sha256', $cuerpo, $secreto);
        }

        return $this->call('POST', route('webhooks.whatsapp.recibir'), [], [], [], $servidor, $cuerpo);
    }

    /**
     * Manda al webhook un aviso con la forma que usa Meta, firmado en $cabecera (la de Meta, o
     * la de Cosend). $secreto null = sin firma.
     */
    private function enviarEstados(array $estados, ?string $secreto = self::SECRETO, string $campo = 'messages', string $cabecera = self::FIRMA_META): TestResponse
    {
        return $this->enviarCuerpo($this->cuerpoDeMeta($estados, $campo), $secreto === null ? [] : [$cabecera => $secreto]);
    }

    public function test_meta_verifica_la_url_con_el_token_correcto(): void
    {
        $this->get(route('webhooks.whatsapp.verificar', [
            'hub.mode'         => 'subscribe',
            'hub.verify_token' => 'token-verificacion',
            'hub.challenge'    => '1158201444',
        ]))->assertOk()->assertContent('1158201444');
    }

    public function test_la_verificacion_falla_con_otro_token_o_sin_token_configurado(): void
    {
        $parametros = ['hub.mode' => 'subscribe', 'hub.verify_token' => 'otro', 'hub.challenge' => '1'];
        $this->get(route('webhooks.whatsapp.verificar', $parametros))->assertForbidden();

        config(['services.whatsapp.verify_token' => null]);
        $parametros['hub.verify_token'] = '';
        $this->get(route('webhooks.whatsapp.verificar', $parametros))->assertForbidden();
    }

    public function test_rechaza_avisos_sin_firma_valida(): void
    {
        $aviso = $this->avisoEnviado();

        $this->enviarEstados([$this->estado('delivered')], secreto: 'otro-secreto')->assertForbidden();
        $this->enviarEstados([$this->estado('delivered')], secreto: null)->assertForbidden();

        config(['services.whatsapp.app_secret' => null]);
        $this->enviarEstados([$this->estado('delivered')], secreto: '')->assertForbidden();

        $this->assertNull($aviso->fresh()->entregado_at);
    }

    public function test_entregado_y_leido_quedan_en_el_aviso(): void
    {
        $aviso = $this->avisoEnviado();

        $this->enviarEstados([$this->estado('sent'), $this->estado('delivered')])->assertOk();
        $aviso->refresh();
        $this->assertSame(self::MOMENTO, $aviso->entregado_at->timestamp);
        $this->assertSame('WhatsApp ✓✓ entregado', $aviso->paraVista()['etiqueta']);

        $this->enviarEstados([$this->estado('read', self::MOMENTO + 300)])->assertOk();
        $aviso->refresh();
        $this->assertSame(self::MOMENTO + 300, $aviso->leido_at->timestamp);
        $this->assertSame('leido', $aviso->paraVista()['entrega']);
        $this->assertSame('WhatsApp ✓✓ leído', $aviso->paraVista()['etiqueta']);
    }

    public function test_los_estados_en_desorden_no_retroceden(): void
    {
        $aviso = $this->avisoEnviado();

        // Meta a veces manda el "read" antes que el "delivered"
        $this->enviarEstados([$this->estado('read')])->assertOk();
        $this->enviarEstados([$this->estado('delivered', self::MOMENTO + 60)])->assertOk();
        // Y un "failed" tardío no deshace un mensaje que ya llegó
        $this->enviarEstados([$this->estado('failed', self::MOMENTO + 120, ['errors' => [['code' => 131026]]])])->assertOk();

        $aviso->refresh();
        $this->assertSame('enviado', $aviso->estado->value);
        $this->assertSame(self::MOMENTO, $aviso->entregado_at->timestamp);
        $this->assertSame(self::MOMENTO, $aviso->leido_at->timestamp);
    }

    public function test_si_no_se_pudo_entregar_queda_fallido_y_se_puede_reintentar(): void
    {
        $aviso = $this->avisoEnviado();

        $this->enviarEstados([$this->estado('failed', extra: [
            'errors' => [['code' => 131026, 'title' => 'Message undeliverable']],
        ])])->assertOk();

        $aviso->refresh();
        $this->assertSame('fallido', $aviso->estado->value);
        $this->assertSame('Meta no lo pudo entregar: El número no tiene WhatsApp o no puede recibir mensajes.', $aviso->detalle);

        // Como ya no hay un aviso enviado, el botón "Reintentar" sí manda uno nuevo
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.REINTENTO']]])]);

        $this->actingAs($this->usuario)
            ->postJson(route('admin.reparaciones.enviarWhatsapp', $aviso->reparacion_id))
            ->assertOk()
            ->assertJsonPath('notificacion.estado', 'enviado');

        Http::assertSentCount(1);
    }

    public function test_ignora_mensajes_y_avisos_que_no_son_de_la_plataforma(): void
    {
        $aviso = $this->avisoEnviado();

        $this->enviarEstados([$this->estado('delivered', extra: ['id' => 'wamid.DE_OTRO_SISTEMA'])])->assertOk();
        $this->enviarEstados([$this->estado('delivered')], campo: 'account_update')->assertOk();

        $this->assertNull($aviso->fresh()->entregado_at);
    }

    public function test_no_abre_sesion_ni_pide_token_csrf(): void
    {
        $this->avisoEnviado();

        $respuesta = $this->enviarEstados([$this->estado('delivered')])->assertOk();

        $this->assertEmpty($respuesta->headers->getCookies());
    }

    // ── Cosend: reenvía los avisos de Meta, firmados con el signing secret del número ──

    public function test_acepta_los_avisos_que_reenvia_cosend(): void
    {
        $aviso = $this->avisoEnviado();

        $this->enviarEstados([$this->estado('delivered')], cabecera: self::FIRMA_COSEND)->assertOk();
        $this->enviarEstados([$this->estado('read', self::MOMENTO + 300)], cabecera: self::FIRMA_COSEND)->assertOk();

        $aviso->refresh();
        $this->assertSame(self::MOMENTO, $aviso->entregado_at->timestamp);
        $this->assertSame(self::MOMENTO + 300, $aviso->leido_at->timestamp);
    }

    public function test_rechaza_avisos_de_cosend_sin_firma_valida(): void
    {
        $aviso = $this->avisoEnviado();

        $this->enviarEstados([$this->estado('delivered')], secreto: 'otro-secreto', cabecera: self::FIRMA_COSEND)->assertForbidden();

        $this->assertNull($aviso->fresh()->entregado_at);
    }

    /**
     * Cosend puede dejar también la firma original de Meta, hecha con el secreto de su propia
     * app (que la plataforma no tiene): con una firma válida basta.
     */
    public function test_con_las_dos_firmas_basta_una_valida(): void
    {
        $aviso  = $this->avisoEnviado();
        $cuerpo = $this->cuerpoDeMeta([$this->estado('delivered')]);

        $this->enviarCuerpo($cuerpo, [self::FIRMA_META => 'secreto-de-la-app-de-cosend', self::FIRMA_COSEND => self::SECRETO])
            ->assertOk();

        $this->assertSame(self::MOMENTO, $aviso->fresh()->entregado_at->timestamp);
    }

    /**
     * Cosend reenvía «un cambio por entrega»: si llega el cambio sin el sobre de Meta (sin
     * entry), también se lee. Uno que no es de mensajes se ignora, igual que dentro del sobre.
     */
    public function test_lee_un_cambio_suelto_sin_el_sobre_de_meta(): void
    {
        $aviso = $this->avisoEnviado();

        $this->enviarCuerpo(json_encode($this->cambio([$this->estado('delivered')], 'account_update')), [self::FIRMA_COSEND => self::SECRETO])
            ->assertOk();
        $this->assertNull($aviso->fresh()->entregado_at);

        $this->enviarCuerpo(json_encode($this->cambio([$this->estado('delivered')])), [self::FIRMA_COSEND => self::SECRETO])
            ->assertOk();
        $this->assertSame(self::MOMENTO, $aviso->fresh()->entregado_at->timestamp);
    }
}
