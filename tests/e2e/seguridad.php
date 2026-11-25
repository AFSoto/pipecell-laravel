<?php

/**
 * Las pruebas de navegador borran y llenan su base: estas revisiones impiden que eso pase
 * con otra que no sea la SQLite temporal que creó correr.mjs (nunca MySQL, nunca
 * database/database.sqlite). Las usan preparar-base.php, cambiar.php y pantallas.php.
 */

/** La ruta de la base temporal (DB_DATABASE), o aborta si no es una de las pruebas. */
function baseTemporalOAbortar(): string
{
    $archivo = (string) getenv('DB_DATABASE');

    if (getenv('DB_CONNECTION') !== 'sqlite' || ! str_contains(str_replace('\\', '/', $archivo), '/pipecell-e2e-')) {
        fwrite(STDERR, "ABORTADO: se corre desde tests/e2e/correr.mjs, con una base SQLite temporal.\n");
        exit(1);
    }

    return $archivo;
}

/** Ya cargada la app: confirma que la conexión es esa base (con la config cacheada, no lo sería). */
function confirmarConexionTemporal(string $archivo): void
{
    if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== $archivo) {
        fwrite(STDERR, "ABORTADO: la app no está usando la base temporal (¿config cacheada? php artisan config:clear).\n");
        exit(1);
    }
}
