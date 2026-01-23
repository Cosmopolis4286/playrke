<?php

namespace App\Services\ExchangeRate;

use App\Contracts\ExchangeRateRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class ExchangeRateUpdater
{

    public function __construct(
        protected ExchangeRateRepositoryInterface $repository
    ) {}

    public function updateRates(int $userId): bool
    {


        return true;
    }
}
