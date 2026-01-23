<?php

namespace App\Services\ExchangeRate;

use App\Contracts\ExchangeRateRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class ExchangeRateUpdaterService
{

    public function __construct(
        protected ExchangeRateRepositoryInterface $repository
    ) {}

    public function updateRates(int $userId): bool
    {


        return true;
    }
}
