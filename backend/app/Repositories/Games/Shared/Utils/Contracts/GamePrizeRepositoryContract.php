<?php

namespace App\Repositories\Games\Shared\Utils\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Shared\Utils\GamePrize;
use Illuminate\Database\Eloquent\Collection;

interface GamePrizeRepositoryContract extends BaseRepositoryInterface
{
    public function getValueByDescription($gameId, string $description, string $column = 'id');

    public function getValueByCode($gameId, string $code, string $column = 'id');

    public function pluckGamePrizes($gameId, string $valueColumn, string $indexColumn = 'id'): Collection;

    public function getByGame($gameId): Collection;

    public function getActiveByGame($gameId): Collection;

    public function getInactiveByGame($gameId): Collection;

    public function getByCode(string $code): ?GamePrize;

    public function getByGameAndCode($gameId, string $code): ?GamePrize;

    public function searchByDescription(string $searchTerm): Collection;

    public function createGamePrize($gameId, string $description, string $code, bool $isActive = true): GamePrize;

    public function updateGamePrize($prizeId, array $data): bool;

    public function activatePrize($prizeId): bool;

    public function deactivatePrize($prizeId): bool;

    public function existsByCode(string $code, $gameId = null): bool;

    public function getActivePrizes(): Collection;

    public function getInactivePrizes(): Collection;

    public function getPrizesByStatus(bool $isActive = true): Collection;

    public function bulkActivatePrizes(array $prizeIds): int;

    public function bulkDeactivatePrizes(array $prizeIds): int;

    public function getGamePrizeStatistics($gameId): array;

    public function getPopularPrizes(): Collection;

    public function getUnusedCodes($gameId): Collection;

    public function generateUniqueCode($gameId): string;
}