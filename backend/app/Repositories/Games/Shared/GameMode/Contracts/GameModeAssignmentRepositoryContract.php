<?php

namespace App\Repositories\Games\Shared\GameMode\Contracts;

use App\Repositories\Base\Contracts\BaseRepositoryContract;
use App\Models\Games\Shared\GameMode\GameModeAssignment;
use Illuminate\Database\Eloquent\Collection;

interface GameModeAssignmentRepositoryContract extends BaseRepositoryContract
{
    public function getByGame($gameId): Collection;

    public function getByGameMode($gameModeId): Collection;

    public function getActiveAssignments(): Collection;

    public function getInactiveAssignments(): Collection;

    public function getByGameAndMode($gameId, $gameModeId): ?GameModeAssignment;

    public function existsAssignment($gameId, $gameModeId): bool;

    public function activateAssignment($gameId, $gameModeId): bool;

    public function deactivateAssignment($gameId, $gameModeId): bool;

    public function createAssignment($gameId, $gameModeId, string $status = 'ACTIVE'): GameModeAssignment;

    public function updateAssignmentStatus($assignmentId, string $status): bool;

    public function deleteAssignment($gameId, $gameModeId): bool;

    public function getActiveGamesByMode($gameModeId): Collection;

    public function getActiveModesByGame($gameId): Collection;

    public function bulkActivateAssignments(array $assignmentIds): int;

    public function bulkDeactivateAssignments(array $assignmentIds): int;

    public function getAssignmentsByStatus(string $status = 'ACTIVE'): Collection;

    public function toggleAssignmentStatus($assignmentId): bool;

    public function getAssignmentStatistics(): array;

    public function getGamesWithoutModes(): Collection;

    public function getModesWithoutGames(): Collection;
}