<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Contratos
use App\Repositories\Contracts\BaseRepositoryContract;
use App\Repositories\Contracts\Users\UserRepositoryContract;
use App\Repositories\Contracts\Games\Bingo\BingoRoomRepositoryContract;
use App\Repositories\Contracts\Commands\CommandLogRepositoryContract;
use App\Repositories\Contracts\Banks\BankVenezuelaRepositoryContract;
use App\Repositories\Contracts\Countries\CountryRepositoryContract;
use App\Repositories\Contracts\Currencies\CurrencyRepositoryContract;
use App\Repositories\Contracts\Games\GameRepositoryContract;
use App\Repositories\Contracts\Games\Utils\GameConfigRepositoryContract;
use App\Repositories\Contracts\Wallets\Crypto\BinanceAccountsRepositoryContract;

// Implementaciones
use App\Repositories\BaseRepository;
use App\Repositories\Users\UserRepository;
use App\Repositories\Games\Bingo\BingoRoomRepository;
use App\Repositories\Commands\CommandLogRepository;
use App\Repositories\Banks\BankVenezuelaRepository;
use App\Repositories\Countries\CountryRepository;
use App\Repositories\Currencies\CurrencyRepository;
use App\Repositories\Games\GameRepository;
use App\Repositories\Games\Utils\GameConfigRepository;
use App\Repositories\Wallets\Crypto\BinanceAccountsRepository;

/**
 * Service Provider para registrar los repositorios
 * Facilita la inyección de dependencias usando los contratos
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Registrar servicios
     *
     * @return void
     */
    public function register(): void
    {
        // Registrar repositorios de usuarios
        $this->app->bind(UserRepositoryContract::class, UserRepository::class);

        // Registrar repositorios de juegos - Bingo
        $this->app->bind(BingoRoomRepositoryContract::class, BingoRoomRepository::class);

        // Registrar repositorios de bancos
        $this->app->bind(BankVenezuelaRepositoryContract::class, BankVenezuelaRepository::class);

        // Registrar repositorios de comandos
        $this->app->bind(CommandLogRepositoryContract::class, CommandLogRepository::class);

        // Registrar repositorios de países
        $this->app->bind(CountryRepositoryContract::class, CountryRepository::class);

        // Registrar repositorios de monedas
        $this->app->bind(CurrencyRepositoryContract::class, CurrencyRepository::class);

        // Registrar repositorios de juegos
        $this->app->bind(GameRepositoryContract::class, GameRepository::class);
        $this->app->bind(GameConfigRepositoryContract::class, GameConfigRepository::class);

        // Registrar repositorios de wallets
        $this->app->bind(BinanceAccountsRepositoryContract::class, BinanceAccountsRepository::class);

        // Registrar otros repositorios según se vayan creando
        // $this->app->bind(WalletRepositoryContract::class, WalletRepository::class);
    }

    /**
     * Inicializar servicios después del registro
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Obtener servicios proporcionados por este provider
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            UserRepositoryContract::class,
            BingoRoomRepositoryContract::class,
            BankVenezuelaRepositoryContract::class,
            CommandLogRepositoryContract::class,
            CountryRepositoryContract::class,
            CurrencyRepositoryContract::class,
            GameRepositoryContract::class,
            GameConfigRepositoryContract::class,
            BinanceAccountsRepositoryContract::class,
        ];
    }
}