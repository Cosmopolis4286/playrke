<?php

/**
 * Bootstrap de PHPUnit. Corre ANTES que cualquier clase de test se instancie
 * o que RefreshDatabase/DatabaseTransactions tengan oportunidad de tocar la
 * base de datos — es el único punto que garantiza el orden correcto sin
 * depender de cómo PHPUnit mezcla los setUp() de traits vs TestCase.
 *
 * ⚠ CAUSA DE INCIDENTES REALES (2026-08-18, 2026-08-24): si se corre
 * `php artisan test` dentro del contenedor Docker sin exportar .env.testing
 * primero (p.ej. `docker exec playrke_php php artisan test` directo,
 * saltándose test-docker.sh), env_file: .env.dev del compose ya fijó
 * DB_DATABASE=playrke (la real) como variable de entorno del PROCESO.
 * Dotenv nunca sobrescribe una variable ya presente en el entorno, así que
 * .env.testing se "carga" pero cada valor es ignorado en silencio, y
 * RefreshDatabase migra/trunca la BD real de desarrollo.
 *
 * Poner el guard aquí (en vez de en tests/TestCase.php::setUp()) importa:
 * un guard dentro de setUp() puede correr DESPUÉS de que el setUp() del
 * trait RefreshDatabase ya haya migrado, dependiendo del orden en que PHP
 * mezcla los métodos de traits vs la clase — como se confirmó en la
 * práctica (la migración alcanzó a ejecutar antes del exit(1)). Este
 * archivo corre antes de que exista ninguna instancia de test.
 */

require __DIR__.'/../vendor/autoload.php';

$rawEnvPath = __DIR__.'/../.env.testing';

if (! is_file($rawEnvPath)) {
    fwrite(STDERR, PHP_EOL.'❌ ABORTADO: no se encontró .env.testing en la raíz de backend/.'.PHP_EOL.PHP_EOL);
    exit(1);
}

$expected = [
    'DB_DATABASE' => null,
    'DB_TRIVIA_DATABASE' => null,
];

foreach (array_keys($expected) as $key) {
    $line = null;

    foreach (file($rawEnvPath) as $rawLine) {
        if (preg_match('/^'.preg_quote($key, '/').'=(.*)$/', trim($rawLine), $matches)) {
            $line = trim($matches[1], " \t\n\r\0\x0B\"'");
        }
    }

    if ($line === null) {
        fwrite(STDERR, PHP_EOL."❌ ABORTADO: {$key} no está definido en .env.testing.".PHP_EOL.PHP_EOL);
        exit(1);
    }

    $expected[$key] = $line;
}

// Aplica .env.testing directamente al entorno del PROCESO, con prioridad
// sobre cualquier variable heredada del contenedor (env_file: .env.dev).
// Esto reemplaza la necesidad de que el invocador haga `set -a; . .env.testing`
// a mano — ya no importa si se corre via test-docker.sh, docker exec directo,
// una tarea de IDE, o cualquier otro runner.
foreach (file($rawEnvPath) as $rawLine) {
    $rawLine = trim($rawLine);

    if ($rawLine === '' || str_starts_with($rawLine, '#') || ! str_contains($rawLine, '=')) {
        continue;
    }

    [$key, $value] = explode('=', $rawLine, 2);
    $key = trim($key);
    $value = trim($value, " \t\n\r\0\x0B\"'");

    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

// Verificación de seguridad final: confirma que, tras forzar .env.testing al
// entorno del proceso, las variables resueltas son inequívocamente las de
// test. Si algo más adelante (config cacheada, etc.) las pisara, esto no lo
// detectaría — por eso TestCase también guarda vía config() resuelto.
if (getenv('DB_DATABASE') !== $expected['DB_DATABASE'] || ! str_contains(getenv('DB_DATABASE'), 'testing')) {
    fwrite(STDERR, PHP_EOL."❌ ABORTADO: DB_DATABASE resuelto ('".getenv('DB_DATABASE')."') no contiene 'testing'.".PHP_EOL.PHP_EOL);
    exit(1);
}

if (getenv('DB_TRIVIA_DATABASE') !== $expected['DB_TRIVIA_DATABASE'] || ! str_contains(getenv('DB_TRIVIA_DATABASE'), 'testing')) {
    fwrite(STDERR, PHP_EOL."❌ ABORTADO: DB_TRIVIA_DATABASE resuelto ('".getenv('DB_TRIVIA_DATABASE')."') no contiene 'testing'.".PHP_EOL.PHP_EOL);
    exit(1);
}
