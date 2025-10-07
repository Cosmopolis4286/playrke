<?php

namespace App\Repositories\Games\Shared\GameMode;

use App\Models\Games\Shared\GameMode\GameModeAssignment;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Games\Shared\GameMode\Contracts\GameModeAssignmentRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

class GameModeAssignmentRepository extends BaseRepository implements GameModeAssignmentRepositoryContract
{
    public function __construct(GameModeAssignment $model)
    {
        parent::__construct($model);
    }

    public function getByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)->get();
    }

    public function getByGameMode($gameModeId): Collection
    {
        return $this->model->where('game_mode_id', $gameModeId)->get();
    }

    public function getActiveAssignments(): Collection
    {
        return $this->model->where('status', 'ACTIVE')->get();
    }

    public function getInactiveAssignments(): Collection
    {
        return $this->model->where('status', 'INACTIVE')->get();
    }

    public function getByGameAndMode($gameId, $gameModeId): ?GameModeAssignment
    {
        return $this->model->where('game_id', $gameId)
            ->where('game_mode_id', $gameModeId)
            ->first();
    }

    public function existsAssignment($gameId, $gameModeId): bool
    {
        return $this->model->where('game_id', $gameId)
            ->where('game_mode_id', $gameModeId)
            ->exists();
    }

    public function activateAssignment($gameId, $gameModeId): bool
    {
        return $this->model->where('game_id', $gameId)
            ->where('game_mode_id', $gameModeId)
            ->update(['status' => 'ACTIVE']) > 0;
    }

    public function deactivateAssignment($gameId, $gameModeId): bool
    {
        return $this->model->where('game_id', $gameId)
            ->where('game_mode_id', $gameModeId)
            ->update(['status' => 'INACTIVE']) > 0;
    }

    public function createAssignment($gameId, $gameModeId, string $status = 'ACTIVE'): GameModeAssignment
    {
        return $this->model->create([
            'game_id' => $gameId,
            'game_mode_id' => $gameModeId,
            'status' => $status
        ]);
    }

    public function updateAssignmentStatus($assignmentId, string $status): bool
    {
        return $this->model->where('id', $assignmentId)
            ->update(['status' => $status]) > 0;
    }

    public function deleteAssignment($gameId, $gameModeId): bool
    {
        return $this->model->where('game_id', $gameId)
            ->where('game_mode_id', $gameModeId)
            ->delete() > 0;
    }

    public function getActiveGamesByMode($gameModeId): Collection
    {
        return $this->model->where('game_mode_id', $gameModeId)
            ->where('status', 'ACTIVE')
            ->with('game')
            ->get();
    }

    public function getActiveModesByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->where('status', 'ACTIVE')
            ->with('gameMode')
            ->get();
    }

    public function bulkActivateAssignments(array $assignmentIds): int
    {
        return $this->model->whereIn('id', $assignmentIds)
            ->update(['status' => 'ACTIVE']);
    }

    public function bulkDeactivateAssignments(array $assignmentIds): int
    {
        return $this->model->whereIn('id', $assignmentIds)
            ->update(['status' => 'INACTIVE']);
    }

    public function getAssignmentsByStatus(string $status = 'ACTIVE'): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    public function toggleAssignmentStatus($assignmentId): bool
    {
        $assignment = $this->model->find($assignmentId);

        if ($assignment) {
            $newStatus = $assignment->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
            return $assignment->update(['status' => $newStatus]);
        }

        return false;
    }

    public function getAssignmentStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('status', 'ACTIVE')->count();
        $inactive = $this->model->where('status', 'INACTIVE')->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0
        ];
    }

    public function getGamesWithoutModes(): Collection
    {
        return $this->model->selectRaw('games.id, games.name')
            ->rightJoin('games', 'games.id', '=', 'game_mode_assignment.game_id')
            ->whereNull('game_mode_assignment.game_id')
            ->get();
    }

    public function getModesWithoutGames(): Collection
    {
        return $this->model->selectRaw('game_modes.id, game_modes.description')
            ->rightJoin('game_modes', 'game_modes.id', '=', 'game_mode_assignment.game_mode_id')
            ->whereNull('game_mode_assignment.game_mode_id')
            ->get();
    }
}