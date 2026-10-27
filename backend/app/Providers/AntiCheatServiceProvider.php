<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Service Provider del sistema Anti-Cheat, compartido entre juegos
 * (Bingo, Trivia, ...). Vive fuera de Domains/Games/* porque no pertenece
 * a ningún juego específico — igual que GamePrizeService en Services/Games.
 */
class AntiCheatServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot() : void
    {
        // database/migrations/anticheat/ no se escanea automáticamente por
        // artisan migrate/migrate:fresh — mismo motivo que
        // BingoServiceProvider/TriviaServiceProvider.
        $this->loadMigrationsFrom(database_path('migrations/anticheat'));

        $this->mergeConfigFrom(config_path('anticheat.php'), 'anticheat');
    }

    /**
     * Register services.
     */
    public function register() : void
    {
        // AntiCheatService no tiene dependencias de contrato que resolver
        // dinámicamente (usa Eloquent + config directamente) — no requiere
        // binding de interfaz->implementación. Se registra como singleton
        // para evitar reconstrucciones redundantes dentro del mismo request.
        $this->app->singleton(\App\Services\Games\AntiCheatService::class);
    }
}
