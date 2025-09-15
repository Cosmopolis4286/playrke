<?php

namespace App\Repositories\Games\Shared\Utils\Contracts;

use App\Repositories\Contracts\BaseRepositoryContract;
use App\Models\Games\Shared\Utils\GameExchangeRate;
use Illuminate\Database\Eloquent\Collection;

interface GameExchangeRateRepositoryContract extends BaseRepositoryContract
{
    public function getGameExchangeRateValue($gameId, int $room, $currencyId, string $column = 'exchange_rate');

    public function getCountGameExchangeRate($gameId, int $room): int;

    public function existsGameExchangeRate($gameId, int $room, $currencyId): bool;

    public function getByGame($gameId): Collection;

    public function getByGameAndRoom($gameId, int $room): Collection;

    public function getByCurrency($currencyId): Collection;

    public function getByGameAndCurrency($gameId, $currencyId): Collection;

    public function createOrUpdateExchangeRate($gameId, int $room, $currencyId, float $exchangeRate): GameExchangeRate;

    public function updateExchangeRate($gameId, int $room, $currencyId, float $exchangeRate): bool;

    public function deleteExchangeRate($gameId, int $room, $currencyId): bool;

    public function getExchangeRatesInRange(float $minRate, float $maxRate): Collection;

    public function getHighestRateByGame($gameId): ?GameExchangeRate;

    public function getLowestRateByGame($gameId): ?GameExchangeRate;

    public function getAverageRateByGame($gameId): float;

    public function getRoomsByGame($gameId): Collection;

    public function getCurrenciesByGame($gameId): Collection;

    public function getGameStatistics($gameId): array;

    public function bulkUpdateRates($gameId, int $room, array $rates): int;
}