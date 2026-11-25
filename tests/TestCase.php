<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ninguna prueba sale a Internet: un pedido sin Http::fake() falla. Es la segunda
        // barrera (la primera, las WHATSAPP_* de phpunit.xml): una clave de verdad en el .env
        // nunca le escribe a nadie desde las pruebas
        Http::preventStrayRequests();

        $this->agregarFuncionesDeMysqlASqlite();
    }

    /**
     * Las pruebas usan SQLite y la app MySQL. La gráfica de ingresos del Dashboard usa MONTH(),
     * que SQLite no tiene: se agrega aquí, en todos los tests, para que el Dashboard abra desde
     * cualquiera y en cualquier orden. (Registrarla en un solo test la dejaba para los que
     * corrían después en la misma conexión, y el resultado dependía del orden.)
     */
    private function agregarFuncionesDeMysqlASqlite(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $pdo = DB::connection()->getPdo();
        $mes = fn (?string $fecha) => $fecha === null ? null : (int) substr($fecha, 5, 2);

        // PHP 8.4 trae Pdo\Sqlite::createFunction; en 8.3 es sqliteCreateFunction
        $pdo instanceof \Pdo\Sqlite
            ? $pdo->createFunction('MONTH', $mes, 1)
            : $pdo->sqliteCreateFunction('MONTH', $mes, 1);
    }
}
