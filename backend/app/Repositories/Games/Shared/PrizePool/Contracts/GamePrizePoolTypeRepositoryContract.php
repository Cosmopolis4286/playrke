<?php

namespace App\Repositories\Shared\PrizePool\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Games\Shared\PrizePool\GamePrizePoolType;
use Illuminate\Database\Eloquent\Collection;

interface GamePrizePoolTypeRepositoryContract extends BaseRepositoryInterface
{
    public function getByGame($gameId): Collection;

    public function getByGameMode($gameModeId): Collection;

    public function getByPrizePoolType($prizePoolTypeId): Collection;

    public function getByGameAndMode($gameId, $gameModeId): Collection;

    public function getActiveAssignments(): Collection;

    public function getInactiveAssignments(): Collection;

    public function getBlockedAssignments(): Collection;

    public function getByGameModeAndPrizePool($gameModeId, $prizePoolTypeId): ?GamePrizePoolType;

    public function existsAssignment($gameId, $gameModeId, $prizePoolTypeId): bool;

    public function createAssignment($gameId, $gameModeId, $prizePoolTypeId, string $status = 'ACTIVE'): GamePrizePoolType;

    public function updateAssignmentStatus($assignmentId, string $status): bool;

    public function activateAssignment($assignmentId): bool;

    public function deactivateAssignment($assignmentId): bool;

    public function blockAssignment($assignmentId): bool;

    public function deleteAssignment($gameId, $gameModeId, $prizePoolTypeId): bool;

    public function getAssignmentsByStatus(string $status = 'ACTIVE'): Collection;

    public function bulkUpdateStatus(array $assignmentIds, string $status): int;

    public function getGameModesByGame($gameId): Collection;

    public function getPrizePoolsByGameMode($gameModeId): Collection;

    public function getAssignmentStatistics(): array;

    public function getGameStatistics($gameId): array;

    public function getUnassignedGameModes($gameId): Collection;

    public function getUnassignedPrizePools($gameModeId): Collection;
}
