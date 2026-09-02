<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * RefreshDatabase corre `migrate:fresh` UNA vez por proceso (ver
     * RefreshDatabaseState::$migrated — no por clase de test), y ningún test
     * declaraba $seed/$seeder, así que catálogos base como `permissions`
     * (PermissionSeeder) quedaban vacíos toda la corrida. Cualquier test que
     * dependiera de un permiso real (ej. DashboardWidgetFilter ->
     * $user->hasPermissionTo('games.bingo')) fallaba con
     * PermissionDoesNotExist, de forma no determinística según el orden de
     * ejecución de la suite.
     *
     * $seed=true (DatabaseSeeder completo) se descartó: varios tests (ej.
     * AuthTest::setUp()) crean su propia Country/Currency/Role fijas (ej.
     * iso_code_2 'VE') asumiendo un catálogo vacío — DatabaseSeeder ya siembra
     * países reales y colisiona con UniqueConstraintViolationException.
     * $seeder apunta a un seeder específico en vez del genérico (soportado
     * nativamente por CanConfigureMigrationCommands::migrateFreshUsing(),
     * pasado como --seeder a migrate:fresh, corre ANTES de que empiece
     * cualquier transacción de test): PermissionSeeder es aditivo por nombre
     * único, no toca countries/currencies, y ningún test crea sus propios
     * Permission sueltos.
     */
    protected $seeder = \Database\Seeders\PermissionSeeder::class;

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

    /**
     * "drop table t1, t2, ..., t90" en una sola sentencia (RefreshDatabase,
     * primera vez que migra/dropea en el proceso — ver
     * MySqlGrammar::compileDropAllTables) puede deadlockear en este schema
     * por el volumen de FKs cruzadas entre ~90 tablas (SQLSTATE 40001).
     * Desactivar FK checks (ver refreshApplication() abajo) reduce la
     * incidencia pero NO la elimina del todo — en MySQL 8 el drop multi-tabla
     * también toma metadata locks (MDL) independientes de foreign_key_checks.
     *
     * PENDIENTE (2026-08-31): un reintento automático aquí (capturar
     * QueryException 40001 y reintentar parent::setUp()) se probó y se
     * descartó — parent::setUp() deja conexiones/estado a medio construir
     * tras un drop fallido, y reintentar sin cerrarlas primero introdujo una
     * condición de carrera nueva (la BD quedaba con un subconjunto distinto
     * de tablas en cada corrida). El fix real probablemente requiere:
     * cerrar explícitamente todas las conexiones del proceso antes de
     * reintentar, o reemplazar el drop multi-tabla de Laravel por drops
     * individuales dentro de un loop con reintento por tabla. No se aplicó
     * hoy — si un test falla con SQLSTATE 40001, reintentar la corrida
     * (bash test-docker.sh) suele bastar mientras tanto.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->guardAgainstNonTestDatabase();
    }

    /**
     * setUpTraits() (dispara RefreshDatabase::setUp(), que migra/dropea el
     * schema) corre ANTES que afterApplicationCreatedCallbacks dentro de
     * setUpTheTestEnvironment() — así que enganchar el fix vía
     * afterApplicationCreated() llega demasiado tarde, después del drop.
     * refreshApplication() es el único punto que corre antes de setUpTraits()
     * y ya deja $this->app listo para resolver el facade DB.
     */
    protected function refreshApplication()
    {
        parent::refreshApplication();

        \Illuminate\Support\Facades\DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=0');
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
