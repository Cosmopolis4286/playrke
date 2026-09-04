<?php

namespace App\Providers;

use App\Domains\Wallet\Contracts\WalletMovementRepositoryInterface;
use App\Domains\Wallet\Contracts\WalletRepositoryInterface;
use App\Domains\Wallet\Repositories\WalletMovementRepository;
use App\Domains\Wallet\Repositories\WalletRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider del dominio Wallet.
 *
 * Registra explícitamente los bindings de repositorios que antes resolvía
 * el auto-scanner de RepositoryServiceProvider (escaneaba app/Repositories/**).
 * Al mover los repositorios de Wallet a app/Domains/Wallet/Repositories,
 * ese scanner deja de encontrarlos — este Provider reemplaza ese mecanismo
 * implícito con bindings explícitos, siguiendo el mismo patrón que
 * BingoServiceProvider/MarketplaceServiceProvider/TriviaServiceProvider
 * (RepositoryServiceProvider::registerRepositoriesAutomatically() solo
 * escanea app/Repositories, no app/Domains/**\/Repositories).
 *
 * No hay boot()/loadMigrationsFrom() aquí: a diferencia de Bingo/Marketplace,
 * las migraciones de Wallet (wallets, wallet_movements, wallet_limits,
 * wallet_holds) viven en database/migrations/ raíz, no en una carpeta
 * dedicada database/migrations/wallet/ — Laravel ya las descubre solo.
 */
class WalletServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(WalletRepositoryInterface::class, WalletRepository::class);
        $this->app->singleton(WalletMovementRepositoryInterface::class, WalletMovementRepository::class);
    }
}
