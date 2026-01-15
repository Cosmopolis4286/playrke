<?php

namespace App\Repositories\Games\Shared\GameRoom\Contracts;

use App\Repositories\Base\Contracts\BaseRepositoryContract;
use App\Models\Shared\GameRoom\GameRoomJackpot;
use Illuminate\Database\Eloquent\Collection;

interface GameRoomJackpotRepositoryContract extends BaseRepositoryContract
{
    public function getByCurrency($currencyId): Collection;

    public function getAvailableJackpots(): Collection;

    public function getUsedJackpots(): Collection;

    public function getJackpotsByStatus(string $status): Collection;

    public function getJackpotsByAmountRange(float $minAmount, float $maxAmount): Collection;

    public function getAvailableJackpotsByCurrency($currencyId): Collection;

    public function getMaxJackpotByCurrency($currencyId): ?GameRoomJackpot;

    public function getMinJackpotByCurrency($currencyId): ?GameRoomJackpot;

    public function getTotalJackpotAmountByCurrency($currencyId): float;

    public function getTotalAvailableJackpotAmount(): float;

    public function createJackpot($currencyId, float $amount, string $status = 'AVAILABLE'): GameRoomJackpot;

    public function markAsUsed($jackpotId): bool;

    public function markAsAvailable($jackpotId): bool;

    public function updateAmount($jackpotId, float $amount): bool;

    public function getJackpotStatistics(): array;

    public function getCurrencyStatistics($currencyId): array;

    public function bulkUpdateStatus(array $jackpotIds, string $status): int;

    public function deleteJackpot($jackpotId): bool;

    public function getJackpotsAboveAmount(float $amount): Collection;

    public function getJackpotsBelowAmount(float $amount): Collection;

    public function getRecentJackpots(int $days = 30): Collection;

    public function getTopJackpotsByCurrency($currencyId, int $limit = 10): Collection;
}