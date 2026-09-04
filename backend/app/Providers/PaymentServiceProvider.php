<?php

namespace App\Providers;

use App\Domains\Payments\Contracts\BlockchainProviderInterface;
use App\Domains\Payments\Exceptions\PaymentsProviderMisconfiguredException;
use App\Domains\Payments\Providers\MockBlockchainProvider;
use App\Domains\Payments\Providers\TronGridProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider del dominio Payments (compras de packs con USDT-TRC20).
 * Ver PAYMENTS_USDT_TRC20_DESIGN.md. Mismo rol que MarketplaceServiceProvider:
 * bindings explícitos + carpeta de migraciones dedicada, ya que
 * RepositoryServiceProvider::registerRepositoriesAutomatically() no escanea
 * app/Domains/**.
 *
 * BlockchainProviderInterface -> TronGridProvider|MockBlockchainProvider según
 * PAYMENTS_TRON_PROVIDER: este es el ÚNICO lugar que conoce el proveedor
 * concreto. Cambiar de proveedor en el futuro (otro indexador, nodo propio,
 * otra red) es un binding nuevo aquí, ningún consumidor cambia.
 *
 * 'mock' nunca se bindea en producción — falla explícito en boot antes que
 * arriesgar confirmar un pago real contra datos simulados.
 */
class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BlockchainProviderInterface::class, function () {
            $provider = config('payments.tron.provider', 'tron_grid');

            if ($provider === 'mock') {
                if ($this->app->environment('production')) {
                    throw new PaymentsProviderMisconfiguredException(
                        'PAYMENTS_TRON_PROVIDER=mock no puede usarse en producción.'
                    );
                }

                return $this->app->make(MockBlockchainProvider::class);
            }

            return $this->app->make(TronGridProvider::class);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/payments'));
    }
}
