<?php

namespace App\Providers;

use App\Domains\Trivia\Contracts\GameEngineInterface;
use App\Domains\Trivia\Contracts\GameStateRepositoryInterface;
use App\Domains\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Trivia\Contracts\ScoreServiceInterface;
use App\Domains\Trivia\Repositories\RedisGameStateRepository;
use App\Domains\Trivia\Services\GameEngineService;
use App\Domains\Trivia\Services\RoomService;
use App\Domains\Trivia\Services\ScoreService;
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
        $this->app->bind(\App\Domains\Trivia\Actions\StartGameAction::class);
        $this->app->bind(\App\Domains\Trivia\Actions\SubmitAnswerAction::class);
        $this->app->bind(\App\Domains\Trivia\Actions\FinishGameAction::class);
        $this->app->bind(\App\Domains\Trivia\Actions\JoinRoomAction::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Cargar rutas de trivia
        $this->loadRoutesFrom(base_path('routes/trivia.php'));

        // Cargar configuración
        $this->mergeConfigFrom(
            config_path('trivia.php'),
            'trivia'
        );
    }
}
