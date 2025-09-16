<?php

namespace App\Repositories\Games\Shared\GameMode\Contracts;

use App\Repositories\Base\Contracts\BaseRepositoryContract;
use App\Models\Games\Shared\GameMode\GameMode;
use Illuminate\Database\Eloquent\Collection;

interface GameModeRepositoryContract extends BaseRepositoryContract
{
    public function getActiveGameModes(): Collection;

    public function getInactiveGameModes(): Collection;

    public function getByDescription(string $description): ?GameMode;

    public function searchByDescription(string $searchTerm): Collection;

    public function activateGameMode($gameModeId): bool;

    public function deactivateGameMode($gameModeId): bool;

    public function createGameMode(string $description, bool $isActive = true): GameMode;

    public function updateGameMode($gameModeId, array $data): bool;

    public function existsByDescription(string $description): bool;

    public function getGameModesWithPrizePoolTypes(): Collection;

    public function getGameModesByStatus(bool $isActive = true): Collection;

    public function toggleGameModeStatus($gameModeId): bool;

    public function bulkActivateGameModes(array $gameModeIds): int;

    public function bulkDeactivateGameModes(array $gameModeIds): int;

    public function getGameModeStatistics(): array;

    public function getRecentlyCreatedGameModes(int $days = 30): Collection;

    public function getPopularGameModes(): Collection;
}