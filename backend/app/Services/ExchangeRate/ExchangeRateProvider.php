<?php

namespace App\Services\ExchangeRate;

use Illuminate\Support\Facades\Http;

class ExchangeRateProvider
{
    public function fetch(): array
    {
        $response = Http::timeout(10)
            ->get('https://smcvenezuela.xyz/dolarsmcbot/binance-api.php')
            ->throw()
            ->json();

        return $response;
    }
}
