<?php

namespace App\Repositories\Games\Common\PrizePool;

use App\Models\Games\Common\PrizePool\GamePrizePoolType;
use App\Repositories\BaseRepository;
use App\Contracts\Games\Common\PrizePool\GamePrizePoolTypeRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

class GamePrizePoolTypeRepository extends BaseRepository implements GamePrizePoolTypeRepositoryContract
{
    public function __construct(GamePrizePoolType $model)
    {
        $this->model = $model;
    }

    public function getByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)->get();
    }

    public function getByGameMode($gameModeId): Collection
    {
        return $this->model->where('connection_type_id', $gameModeId)->get();
    }

    public function getByPrizePoolType($prizePoolTypeId): Collection
    {
        return $this->model->where('prize_pool_type_id', $prizePoolTypeId)->get();
    }

    public function getByGameAndMode($gameId, $gameModeId): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->where('connection_type_id', $gameModeId)
            ->get();
    }

    public function getActiveAssignments(): Collection
    {
        return $this->model->where('status', 'ACTIVE')->get();
    }

    public function getInactiveAssignments(): Collection
    {
        return $this->model->where('status', 'INACTIVE')->get();
    }

    public function getBlockedAssignments(): Collection
    {
        return $this->model->where('status', 'BLOCKED')->get();
    }

    public function getByGameModeAndPrizePool($gameModeId, $prizePoolTypeId): ?GamePrizePoolType
    {
        return $this->model->where('connection_type_id', $gameModeId)
            ->where('prize_pool_type_id', $prizePoolTypeId)
            ->first();
    }

    public function existsAssignment($gameId, $gameModeId, $prizePoolTypeId): bool
    {
        return $this->model->where('game_id', $gameId)
            ->where('connection_type_id', $gameModeId)
            ->where('prize_pool_type_id', $prizePoolTypeId)
            ->exists();
    }

    public function createAssignment($gameId, $gameModeId, $prizePoolTypeId, string $status = 'ACTIVE'): GamePrizePoolType
    {
        return $this->model->create([
            'game_id' => $gameId,
            'connection_type_id' => $gameModeId,
            'prize_pool_type_id' => $prizePoolTypeId,
            'status' => $status
        ]);
    }

    public function updateAssignmentStatus($assignmentId, string $status): bool
    {
        return $this->model->where('id', $assignmentId)
            ->update(['status' => $status]) > 0;
    }

    public function activateAssignment($assignmentId): bool
    {
        return $this->updateAssignmentStatus($assignmentId, 'ACTIVE');
    }

    public function deactivateAssignment($assignmentId): bool
    {
        return $this->updateAssignmentStatus($assignmentId, 'INACTIVE');
    }

    public function blockAssignment($assignmentId): bool
    {
        return $this->updateAssignmentStatus($assignmentId, 'BLOCKED');
    }

    public function deleteAssignment($gameId, $gameModeId, $prizePoolTypeId): bool
    {
        return $this->model->where('game_id', $gameId)
            ->where('connection_type_id', $gameModeId)
            ->where('prize_pool_type_id', $prizePoolTypeId)
            ->delete() > 0;
    }

    public function getAssignmentsByStatus(string $status = 'ACTIVE'): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    public function bulkUpdateStatus(array $assignmentIds, string $status): int
    {
        return $this->model->whereIn('id', $assignmentIds)
            ->update(['status' => $status]);
    }

    public function getGameModesByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->with('connectionType')
            ->get()
            ->pluck('connectionType')
            ->unique('id');
    }

    public function getPrizePoolsByGameMode($gameModeId): Collection
    {
        return $this->model->where('connection_type_id', $gameModeId)
            ->with('prizePoolType')
            ->get()
            ->pluck('prizePoolType')
            ->unique('id');
    }

    public function getAssignmentStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('status', 'ACTIVE')->count();
        $inactive = $this->model->where('status', 'INACTIVE')->count();
        $blocked = $this->model->where('status', 'BLOCKED')->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'blocked' => $blocked,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0
        ];
    }

    public function getGameStatistics($gameId): array
    {
        $total = $this->model->where('game_id', $gameId)->count();
        $active = $this->model->where('game_id', $gameId)
            ->where('status', 'ACTIVE')->count();
        $modes = $this->model->where('game_id', $gameId)
            ->distinct('connection_type_id')->count();
        $prizePools = $this->model->where('game_id', $gameId)
            ->distinct('prize_pool_type_id')->count();

        return [
            'total_assignments' => $total,
            'active_assignments' => $active,
            'total_modes' => $modes,
            'total_prize_pools' => $prizePools
        ];
    }

    public function getUnassignedGameModes($gameId): Collection
    {
        $assignedModeIds = $this->model->where('game_id', $gameId)
            ->pluck('connection_type_id');

        return $this->model->selectRaw('connection_types.id, connection_types.label')
            ->rightJoin('connection_types', 'connection_types.id', '=', 'game_prize_pool_types.connection_type_id')
            ->whereNotIn('connection_types.id', $assignedModeIds)
            ->get();
    }

    public function getUnassignedPrizePools($gameModeId): Collection
    {
        $assignedPoolIds = $this->model->where('connection_type_id', $gameModeId)
            ->pluck('prize_pool_type_id');

        return $this->model->selectRaw('prize_pool_types.id, prize_pool_types.name')
            ->rightJoin('prize_pool_types', 'prize_pool_types.id', '=', 'game_prize_pool_types.prize_pool_type_id')
            ->whereNotIn('prize_pool_types.id', $assignedPoolIds)
            ->get();
    }
}
