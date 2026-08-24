<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Nombres de BD esperados en entorno de testing. Si el proceso arrancó sin
     * exportar .env.testing (p.ej. `docker exec playrke_php php artisan test`
     * directo, saltándose test-docker.sh), env_file: .env.dev del compose ya
     * fijó DB_DATABASE=playrke (la real) como variable de entorno del proceso,
     * y dotenv nunca sobrescribe una variable ya presente en el entorno. El
     * resultado: los tests correrían — y RefreshDatabase truncaría — la BD
     * real de desarrollo. Ver incidentes 2026-08-18 y 2026-08-24.
     */
    private const EXPECTED_TEST_DATABASES = [
        'mysql' => 'playrke_testing',
        'pgsql_trivia' => 'trivia_testing',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->guardAgainstNonTestDatabase();
    }

    private function guardAgainstNonTestDatabase(): void
    {
        foreach (self::EXPECTED_TEST_DATABASES as $connection => $expectedDatabase) {
            $actualDatabase = config("database.connections.{$connection}.database");

            if ($actualDatabase !== $expectedDatabase) {
                fwrite(STDERR, PHP_EOL."❌ ABORTADO: la conexión '{$connection}' resuelve a la base de datos "
                    ."'{$actualDatabase}', se esperaba '{$expectedDatabase}'.".PHP_EOL
                    .'   Esto significa que los tests NO están corriendo con .env.testing aplicado '
                    .'(probablemente se saltó test-docker.sh). Correr así arriesga truncar datos reales.'
                    .PHP_EOL.'   Usa: bash test-docker.sh'.PHP_EOL.PHP_EOL);

                exit(1);
            }
        }
    }
}
