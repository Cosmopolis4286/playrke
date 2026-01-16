<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\ExchangeRate\ExchangeRateProviderInterface;
use App\Services\ExchangeRate\Providers\CommerceExchangeRateProvider;
use App\Services\ExchangeRate\Providers\CustomerExchangeRateProvider;

final class ExchangeRateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Tag de providers de tasas
        $this->app->tag(
            [
                CommerceExchangeRateProvider::class,
                CustomerExchangeRateProvider::class,
            ],
            ExchangeRateProviderInterface::class
        );
    }
}
