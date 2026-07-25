<?php

namespace App\Repositories\Games\Common\GameMode;

use App\Models\Games\Common\GameMode\GameMode;
use App\Repositories\BaseRepository;
use App\Contracts\Games\Common\GameMode\GameModeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class GameModeRepository extends BaseRepository implements GameModeRepositoryInterface
{
    public function __construct(GameMode $model)
    {
        $this->model = $model;
    }

    public function getActiveGameModes(): Collection
    {
        return $this->model->where('status', 'ACTIVE')->get();
    }

    public function getInactiveGameModes(): Collection
    {
        return $this->model->where('status', 'INACTIVE')->get();
    }

    public function getByLabel(string $label): ?GameMode
    {
        return $this->model->where('label', $label)->first();
    }

    public function searchByLabel(string $searchTerm): Collection
    {
        return $this->model->where('label', 'LIKE', "%{$searchTerm}%")->get();
    }

    public function activateGameMode($gameModeId): bool
    {
        return $this->model->where('id', $gameModeId)
            ->update(['status' => 'ACTIVE']) > 0;
    }

    public function deactivateGameMode($gameModeId): bool
    {
        return $this->model->where('id', $gameModeId)
            ->update(['status' => 'INACTIVE']) > 0;
    }

    public function createGameMode(string $code, string $label, bool $isActive = true): GameMode
    {
        return $this->model->create([
            'code' => $code,
            'label' => $label,
            'status' => $isActive ? 'ACTIVE' : 'INACTIVE'
        ]);
    }

    public function updateGameMode($gameModeId, array $data): bool
    {
        return $this->model->where('id', $gameModeId)
            ->update($data) > 0;
    }

    public function existsByLabel(string $label): bool
    {
        return $this->model->where('label', $label)->exists();
    }

    public function getGameModesWithPrizePoolTypes(): Collection
    {
        return $this->model->with('prizePoolTypes')->get();
    }

    public function getGameModesByStatus(bool $isActive = true): Collection
    {
        return $this->model->where('status', $isActive ? 'ACTIVE' : 'INACTIVE')->get();
    }

    public function toggleGameModeStatus($gameModeId): bool
    {
        $gameMode = $this->model->find($gameModeId);

        if ($gameMode) {
            return $gameMode->update(['status' => $gameMode->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE']);
        }

        return false;
    }

    public function bulkActivateGameModes(array $gameModeIds): int
    {
        return $this->model->whereIn('id', $gameModeIds)
            ->update(['status' => 'ACTIVE']);
    }

    public function bulkDeactivateGameModes(array $gameModeIds): int
    {
        return $this->model->whereIn('id', $gameModeIds)
            ->update(['status' => 'INACTIVE']);
    }

    public function getGameModeStatistics(): array
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

    public function getRecentlyCreatedGameModes(int $days = 30): Collection
    {
        $since = Carbon::now()->subDays($days);

        return $this->model->where('created_at', '>=', $since)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getPopularGameModes(): Collection
    {
        return $this->model->withCount('prizePoolTypes')
            ->orderBy('prize_pool_types_count', 'desc')
            ->get();
    }
}
