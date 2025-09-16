<?php

namespace App\Repositories\Games\Shared\Utils;

use App\Models\Games\Shared\Utils\GameExchangeRate;
use App\Repositories\Base\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class GameExchangeRateRepository extends BaseRepository implements GameExchangeRateRepositoryContract
{
    public function __construct(GameExchangeRate $model)
    {
        parent::__construct($model);
    }

    public function getGameExchangeRateValue($gameId, int $room, $currencyId, string $column = 'exchange_rate')
    {
        return $this->model->where('game_id', $gameId)
            ->where('room', $room)
            ->where('currency_id', $currencyId)
            ->value($column);
    }

    public function getCountGameExchangeRate($gameId, int $room): int
    {
        return $this->model->where('game_id', $gameId)
            ->where('room', $room)
            ->count();
    }

    public function existsGameExchangeRate($gameId, int $room, $currencyId): bool
    {
        return $this->model->where('game_id', $gameId)
            ->where('room', $room)
            ->where('currency_id', $currencyId)
            ->exists();
    }

    public function getByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)->get();
    }

    public function getByGameAndRoom($gameId, int $room): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->where('room', $room)
            ->get();
    }

    public function getByCurrency($currencyId): Collection
    {
        return $this->model->where('currency_id', $currencyId)->get();
    }

    public function getByGameAndCurrency($gameId, $currencyId): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->where('currency_id', $currencyId)
            ->get();
    }

    public function createOrUpdateExchangeRate($gameId, int $room, $currencyId, float $exchangeRate): GameExchangeRate
    {
        return $this->model->updateOrCreate(
            [
                'game_id' => $gameId,
                'room' => $room,
                'currency_id' => $currencyId
            ],
            [
                'exchange_rate' => $exchangeRate
            ]
        );
    }

    public function updateExchangeRate($gameId, int $room, $currencyId, float $exchangeRate): bool
    {
        return $this->model->where('game_id', $gameId)
            ->where('room', $room)
            ->where('currency_id', $currencyId)
            ->update(['exchange_rate' => $exchangeRate]) > 0;
    }

    public function deleteExchangeRate($gameId, int $room, $currencyId): bool
    {
        return $this->model->where('game_id', $gameId)
            ->where('room', $room)
            ->where('currency_id', $currencyId)
            ->delete() > 0;
    }

    public function getExchangeRatesInRange(float $minRate, float $maxRate): Collection
    {
        return $this->model->whereBetween('exchange_rate', [$minRate, $maxRate])->get();
    }

    public function getHighestRateByGame($gameId): ?GameExchangeRate
    {
        return $this->model->where('game_id', $gameId)
            ->orderBy('exchange_rate', 'desc')
            ->first();
    }

    public function getLowestRateByGame($gameId): ?GameExchangeRate
    {
        return $this->model->where('game_id', $gameId)
            ->orderBy('exchange_rate', 'asc')
            ->first();
    }

    public function getAverageRateByGame($gameId): float
    {
        return $this->model->where('game_id', $gameId)
            ->avg('exchange_rate') ?? 0.0;
    }

    public function getRoomsByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->select('room')
            ->distinct()
            ->pluck('room');
    }

    public function getCurrenciesByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->select('currency_id')
            ->distinct()
            ->with('currency')
            ->get();
    }

    public function getGameStatistics($gameId): array
    {
        $totalRates = $this->model->where('game_id', $gameId)->count();
        $totalRooms = $this->model->where('game_id', $gameId)
            ->distinct('room')->count();
        $totalCurrencies = $this->model->where('game_id', $gameId)
            ->distinct('currency_id')->count();
        $averageRate = $this->getAverageRateByGame($gameId);

        return [
            'total_rates' => $totalRates,
            'total_rooms' => $totalRooms,
            'total_currencies' => $totalCurrencies,
            'average_rate' => $averageRate
        ];
    }

    public function bulkUpdateRates($gameId, int $room, array $rates): int
    {
        $updated = 0;

        foreach ($rates as $currencyId => $exchangeRate) {
            if ($this->updateExchangeRate($gameId, $room, $currencyId, $exchangeRate)) {
                $updated++;
            }
        }

        return $updated;
    }
}