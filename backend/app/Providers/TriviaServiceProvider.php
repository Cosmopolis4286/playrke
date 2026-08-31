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
    }
}
