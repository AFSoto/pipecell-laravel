<?php

namespace Tests\Unit;

use App\Services\NotificacionService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NormalizarTelefonoTest extends TestCase
{
    public static function telefonos(): array
    {
        return [
            'celular con espacios'      => ['310 123 4567', '573101234567'],
            'celular pegado'            => ['3101234567', '573101234567'],
            'con +57 y guiones'         => ['+57 310-123-4567', '573101234567'],
            'con 57 sin signo'          => ['573101234567', '573101234567'],
            'con paréntesis'            => ['(310) 123 4567', '573101234567'],
            'fijo de Bogotá'            => ['601 234 5678', null],
            'incompleto'                => ['310 123', null],
            'otro país'                 => ['+1 305 555 0100', null],
            'vacío'                     => ['', null],
            'null'                      => [null, null],
        ];
    }

    #[DataProvider('telefonos')]
    public function test_normaliza_celulares_colombianos(?string $entrada, ?string $esperado): void
    {
        $this->assertSame($esperado, NotificacionService::normalizarTelefono($entrada));
    }
}
