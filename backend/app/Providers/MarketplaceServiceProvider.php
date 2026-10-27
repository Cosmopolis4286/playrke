<?php

namespace App\Providers;

use App\Domains\Marketplace\Crypto\Contracts\CryptoGateway;
use App\Domains\Marketplace\Crypto\Providers\TronUsdtProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider del dominio Marketplace (Inventory, Marketplace, Escrow,
 * Crypto — ver MARKETPLACE_ARCHITECTURE_AUDIT.md). Mismo rol que
 * BingoServiceProvider/TriviaServiceProvider: registra bindings explícitos y
 * la carpeta de migraciones dedicada, ya que
 * RepositoryServiceProvider::registerRepositoriesAutomatically() solo
 * escanea app/Repositories, no app/Domains/**\/Repositories.
 *
 * Fase 1 (Inventory) no tiene repositorios con interfaz propia todavía —
 * InventoryService opera directamente sobre los modelos Eloquent, igual que
 * WalletReservationService opera sobre WalletHold sin un repositorio
 * intermedio.
 *
 * CryptoGateway -> TronUsdtProvider (STUB, Fase 5): reemplazar este binding
 * por una implementación real es el ÚNICO cambio necesario para desbloquear
 * custodia real (Fases 6-7) — ver docblock de TronUsdtProvider.
 */
class MarketplaceServiceProvider extends ServiceProvider
{
    public function boot() : void
    {
        $this->loadMigrationsFrom(database_path('migrations/marketplace'));
    }

    public function register() : void
    {
        $this->app->bind(CryptoGateway::class, TronUsdtProvider::class);
    }
}
