<?php

namespace App\Repositories\Shared\GameMode;

use App\Models\Games\Shared\GameMode\GameMode;
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
        return $this->model->where('status', true)->get();
    }

    public function getInactiveGameModes(): Collection
    {
        return $this->model->where('status', false)->get();
    }

    public function getByDescription(string $description): ?GameMode
    {
        return $this->model->where('description', $description)->first();
    }

    public function searchByDescription(string $searchTerm): Collection
    {
        return $this->model->where('description', 'LIKE', "%{$searchTerm}%")->get();
    }

    public function activateGameMode($gameModeId): bool
    {
        return $this->model->where('id', $gameModeId)
            ->update(['status' => true]) > 0;
    }

    public function deactivateGameMode($gameModeId): bool
    {
        return $this->model->where('id', $gameModeId)
            ->update(['status' => false]) > 0;
    }

    public function createGameMode(string $description, bool $isActive = true): GameMode
    {
        return $this->model->create([
            'description' => $description,
            'status' => $isActive
        ]);
    }

    public function updateGameMode($gameModeId, array $data): bool
    {
        return $this->model->where('id', $gameModeId)
            ->update($data) > 0;
    }

    public function existsByDescription(string $description): bool
    {
        return $this->model->where('description', $description)->exists();
    }

    public function getGameModesWithPrizePoolTypes(): Collection
    {
        return $this->model->with('prizePoolTypes')->get();
    }

    public function getGameModesByStatus(bool $isActive = true): Collection
    {
        return $this->model->where('status', $isActive)->get();
    }

    public function toggleGameModeStatus($gameModeId): bool
    {
        $gameMode = $this->model->find($gameModeId);

        if ($gameMode) {
            return $gameMode->update(['status' => !$gameMode->status]);
        }

        return false;
    }

    public function bulkActivateGameModes(array $gameModeIds): int
    {
        return $this->model->whereIn('id', $gameModeIds)
            ->update(['status' => true]);
    }

    public function bulkDeactivateGameModes(array $gameModeIds): int
    {
        return $this->model->whereIn('id', $gameModeIds)
            ->update(['status' => false]);
    }

    public function getGameModeStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('status', true)->count();
        $inactive = $this->model->where('status', false)->count();

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
