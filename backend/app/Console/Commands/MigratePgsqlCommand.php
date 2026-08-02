<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Algunos dominios (hoy: Trivia) viven en conexiones Postgres separadas y
 * completamente aisladas del resto del schema de la app (MySQL) — ver
 * config/database.php, entradas con 'driver' => 'pgsql'. NO correr
 * `migrate`/`migrate:fresh` sin acotar `--database=<conexión> --path=...`
 * contra esas conexiones: correrlas sin acotar arrastra TODAS las
 * migraciones de la app (incluida la de `users`), que no tiene sentido ahí
 * — esos dominios referencian usuarios/juegos de MySQL con FKs blandas, sin
 * constraint física (ver comentarios en database/migrations/trivia/).
 *
 * Este comando es genérico: detecta la carpeta de migraciones que
 * corresponde a la conexión pedida buscando qué archivos bajo
 * database/migrations/** usan Schema::connection('<conexión>') — no asume
 * una sola conexión Postgres hardcodeada.
 */
class MigratePgsqlCommand extends Command
{
    protected $signature = 'db:migrate-pgsql
        {connection? : Nombre de la conexión Postgres a migrar (ej. pgsql_trivia). Si se omite, se listan las disponibles}
        {--fresh : Elimina todas las tablas de la conexión antes de migrar}
        {--seed : Ejecuta los seeders del dominio después de migrar, si existen}';

    protected $description = 'Migra (opcionalmente fresh + seed) solo las migraciones que pertenecen a una conexión Postgres aislada (ej. pgsql_trivia), sin arrastrar el resto del schema de la app';

    /** Mapea conexión → [rutas de migraciones, seeders opcionales] para dominios conocidos. */
    private const KNOWN_DOMAINS = [
        'pgsql_trivia' => [
            'paths'   => ['database/migrations/trivia'],
            'seeders' => [\Database\Seeders\Trivia\TriviaQuestionSeeder::class],
        ],
    ];

    public function handle(): int
    {
        $connection = $this->argument('connection') ?: $this->promptForConnection();

        if (!$connection) {
            return self::FAILURE;
        }

        $paths = $this->resolveMigrationPaths($connection);

        if (empty($paths)) {
            $this->error("No se encontraron migraciones que usen Schema::connection('{$connection}').");
            return self::FAILURE;
        }

        $this->info("Migrando conexión '{$connection}' usando: " . implode(', ', $paths));

        $exitCode = $this->call($this->option('fresh') ? 'migrate:fresh' : 'migrate', [
            '--database' => $connection,
            '--path'     => $paths,
            '--force'    => true,
        ]);

        if ($exitCode !== 0) {
            $this->error("Falló la migración de la conexión '{$connection}'.");
            return $exitCode;
        }

        if ($this->option('seed')) {
            foreach (self::KNOWN_DOMAINS[$connection]['seeders'] ?? [] as $seederClass) {
                $this->call('db:seed', ['--class' => $seederClass]);
            }
        }

        $this->info("Migración de '{$connection}' completada exitosamente.");
        return self::SUCCESS;
    }

    /**
     * Rutas de migraciones para la conexión: usa el mapeo conocido si existe,
     * si no, escanea database/migrations recursivamente buscando archivos
     * que contengan Schema::connection('<conexión>') y agrupa por carpeta.
     */
    private function resolveMigrationPaths(string $connection): array
    {
        if (isset(self::KNOWN_DOMAINS[$connection])) {
            return self::KNOWN_DOMAINS[$connection]['paths'];
        }

        $base = database_path('migrations');
        $matchingDirs = [];

        foreach (File::allFiles($base) as $file) {
            $contents = File::get($file->getPathname());
            if (str_contains($contents, "Schema::connection('{$connection}')")) {
                $matchingDirs[dirname($file->getPathname())] = true;
            }
        }

        return array_map(
            fn (string $dir) => 'database/migrations' . str_replace(str_replace('/', DIRECTORY_SEPARATOR, $base), '', $dir),
            array_keys($matchingDirs)
        );
    }

    /** Lista las conexiones pgsql configuradas y pide al usuario elegir una. */
    private function promptForConnection(): ?string
    {
        $connections = collect(config('database.connections'))
            ->filter(fn (array $cfg) => ($cfg['driver'] ?? null) === 'pgsql')
            ->keys()
            ->all();

        if (empty($connections)) {
            $this->error('No hay conexiones con driver pgsql configuradas en config/database.php.');
            return null;
        }

        if (count($connections) === 1) {
            return $connections[0];
        }

        return $this->choice('¿Qué conexión Postgres deseas migrar?', $connections);
    }
}
