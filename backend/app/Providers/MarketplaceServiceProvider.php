<?php

namespace App\Providers;

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
 * intermedio. Este provider queda listo para los bindings de Marketplace/
 * Escrow de las fases siguientes.
 */
class MarketplaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/marketplace'));
    }
}
