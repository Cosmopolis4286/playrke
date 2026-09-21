<?php

namespace App\Providers;

use App\Domains\Games\Trivia\Contracts\GameEngineInterface;
use App\Domains\Games\Trivia\Contracts\GameStateRepositoryInterface;
use App\Domains\Games\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Games\Trivia\Contracts\ScoreServiceInterface;
use App\Domains\Games\Trivia\Repositories\RedisGameStateRepository;
use App\Domains\Games\Trivia\Services\GameEngineService;
use App\Domains\Games\Trivia\Services\RoomService;
use App\Domains\Games\Trivia\Services\ScoreService;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider del módulo Trivia.
 * 
 * Registra todos los bindings del módulo,
 * preparado para inyección de dependencias.
 */
class TriviaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Repositorios
        $this->app->singleton(
            GameStateRepositoryInterface::class,
            RedisGameStateRepository::class
        );

        // Servicios
        $this->app->singleton(
            RoomServiceInterface::class,
            RoomService::class
        );

        $this->app->singleton(
            ScoreServiceInterface::class,
            ScoreService::class
        );

        $this->app->singleton(
            GameEngineInterface::class,
            GameEngineService::class
        );

        // Actions (transient - nueva instancia cada vez)
        $this->app->bind(\App\Domains\Games\Trivia\Actions\StartGameAction::class);
        $this->app->bind(\App\Domains\Games\Trivia\Actions\SubmitAnswerAction::class);
        $this->app->bind(\App\Domains\Games\Trivia\Actions\FinishGameAction::class);
        $this->app->bind(\App\Domains\Games\Trivia\Actions\JoinRoomAction::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Cargar configuración
        $this->mergeConfigFrom(
            config_path('trivia.php'),
            'trivia'
        );

        // database/migrations/trivia/ no se escanea automáticamente por
        // artisan migrate/migrate:fresh — sin este registro, un entorno
        // nuevo nunca crea las tablas trivia_* aunque el código las use.
        $this->loadMigrationsFrom(database_path('migrations/trivia'));

        $this->guardPgsqlTriviaAgainstHungQueries();
    }

    /**
     * PDO_pgsql no tiene forma de acotar una query ya en ejecución (o una
     * conexión ESTABLISHED cuyo socket dejó de responder) — PDO::ATTR_TIMEOUT
     * solo limita el connect() inicial, confirmado empíricamente: una query
     * pg_sleep(10) con ese timeout en 3s igual tardó los 10s completos. Sin
     * este SET, un contenedor recién reiniciado (Postgres aún inicializando,
     * o una conexión huérfana de una corrida anterior) deja el proceso
     * PHPUnit bloqueado en poll() sin límite de tiempo la primera vez que
     * RefreshDatabase migra trivia_rooms (Schema::connection('pgsql_trivia')
     * ->hasTable(), ver 2024_01_01_001201_create_trivia_rooms_table.php) —
     * causa raíz confirmada vía /proc/[pid]/wchan (do_sys_poll) + pg_stat_
     * activity (wait_event: ClientRead sobre una query ya completada).
     *
     * statement_timeout es un límite de SESIÓN aplicado por el propio
     * servidor Postgres — cancela la query del lado servidor si excede el
     * límite, a diferencia de un timeout de cliente que no puede interrumpir
     * un socket ya bloqueado en lectura.
     */
    private function guardPgsqlTriviaAgainstHungQueries(): void
    {
        $timeoutMs = (int) config('trivia.pgsql_statement_timeout_ms', 15000);

        Event::listen(function (ConnectionEstablished $event) use ($timeoutMs): void {
            if (! in_array($event->connectionName, ['pgsql', 'pgsql_trivia'], true)) {
                return;
            }

            // SET no admite parámetros bindeados en Postgres ("syntax error
            // at or near '$1'") — $timeoutMs ya está forzado a int arriba,
            // así que la interpolación directa es segura.
            $event->connection->statement("SET statement_timeout = {$timeoutMs}");
        });
    }
}
