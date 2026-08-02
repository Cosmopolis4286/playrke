<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * trivia_rooms y las demás tablas del dominio Trivia viven en una conexión
 * Postgres aparte (pgsql_trivia, ver config/database.php) — completamente
 * aislada del resto del schema de la app (MySQL). NO correr `migrate:fresh`
 * (ni `migrate` a secas) sin acotar `--database=pgsql_trivia --path=...`
 * ahí: correrlo sin acotar arrastra TODAS las migraciones de la app contra
 * esa conexión, incluida la de `users`, que en pgsql_trivia no tiene sentido
 * (Trivia referencia usuarios/juegos de MySQL con FKs blandas, sin
 * constraint física — ver comentarios en las migraciones de
 * database/migrations/trivia/).
 */
class MigrateTriviaCommand extends Command
{
    protected $signature = 'trivia:migrate {--fresh : Elimina todas las tablas de pgsql_trivia antes de migrar} {--seed : Ejecuta los seeders de Trivia después de migrar}';

    protected $description = 'Migra (opcionalmente fresh + seed) solo las tablas del dominio Trivia en la conexión Postgres aislada pgsql_trivia';

    public function handle(): int
    {
        $path = 'database/migrations/trivia';

        $exitCode = $this->call($this->option('fresh') ? 'migrate:fresh' : 'migrate', [
            '--database' => 'pgsql_trivia',
            '--path'     => $path,
            '--force'    => true,
        ]);

        if ($exitCode !== 0) {
            $this->error('Falló la migración de Trivia (pgsql_trivia).');
            return $exitCode;
        }

        if ($this->option('seed')) {
            $this->call('db:seed', [
                '--class' => \Database\Seeders\Trivia\TriviaQuestionSeeder::class,
            ]);
        }

        $this->info('Migración de Trivia (pgsql_trivia) completada exitosamente.');
        return self::SUCCESS;
    }
}
