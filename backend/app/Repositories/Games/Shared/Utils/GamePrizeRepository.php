<?php

namespace App\Repositories\Shared\Utils;

use App\Models\Shared\Utils\GamePrize;
use App\Repositories\BaseRepository;
use App\Repositories\Shared\Utils\Contracts\GamePrizeRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class GamePrizeRepository extends BaseRepository implements GamePrizeRepositoryContract
{
    public function __construct(GamePrize $model)
    {
        parent::__construct($model);
    }

    public function getValueByDescription($gameId, string $description, string $column = 'id')
    {
        return $this->model->where('game_id', $gameId)
            ->where('description', $description)
            ->value($column);
    }

    public function getValueByCode($gameId, string $code, string $column = 'id')
    {
        return $this->model->where('game_id', $gameId)
            ->where('code', $code)
            ->value($column);
    }

    public function pluckGamePrizes($gameId, string $valueColumn, string $indexColumn = 'id'): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->pluck($valueColumn, $indexColumn);
    }

    public function getByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)->get();
    }

    public function getActiveByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->where('status', true)
            ->get();
    }

    public function getInactiveByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->where('status', false)
            ->get();
    }

    public function getByCode(string $code): ?GamePrize
    {
        return $this->model->where('code', $code)->first();
    }

    public function getByGameAndCode($gameId, string $code): ?GamePrize
    {
        return $this->model->where('game_id', $gameId)
            ->where('code', $code)
            ->first();
    }

    public function searchByDescription(string $searchTerm): Collection
    {
        return $this->model->where('description', 'LIKE', "%{$searchTerm}%")->get();
    }

    public function createGamePrize($gameId, string $description, string $code, bool $isActive = true): GamePrize
    {
        return $this->model->create([
            'game_id' => $gameId,
            'description' => $description,
            'code' => $code,
            'status' => $isActive
        ]);
    }

    public function updateGamePrize($prizeId, array $data): bool
    {
        return $this->model->where('id', $prizeId)
            ->update($data) > 0;
    }

    public function activatePrize($prizeId): bool
    {
        return $this->model->where('id', $prizeId)
            ->update(['status' => true]) > 0;
    }

    public function deactivatePrize($prizeId): bool
    {
        return $this->model->where('id', $prizeId)
            ->update(['status' => false]) > 0;
    }

    public function existsByCode(string $code, $gameId = null): bool
    {
        $query = $this->model->where('code', $code);

        if ($gameId !== null) {
            $query->where('game_id', $gameId);
        }

        return $query->exists();
    }

    public function getActivePrizes(): Collection
    {
        return $this->model->where('status', true)->get();
    }

    public function getInactivePrizes(): Collection
    {
        return $this->model->where('status', false)->get();
    }

    public function getPrizesByStatus(bool $isActive = true): Collection
    {
        return $this->model->where('status', $isActive)->get();
    }

    public function bulkActivatePrizes(array $prizeIds): int
    {
        return $this->model->whereIn('id', $prizeIds)
            ->update(['status' => true]);
    }

    public function bulkDeactivatePrizes(array $prizeIds): int
    {
        return $this->model->whereIn('id', $prizeIds)
            ->update(['status' => false]);
    }

    public function getGamePrizeStatistics($gameId): array
    {
        $total = $this->model->where('game_id', $gameId)->count();
        $active = $this->model->where('game_id', $gameId)
            ->where('status', true)->count();
        $inactive = $this->model->where('game_id', $gameId)
            ->where('status', false)->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0
        ];
    }

    public function getPopularPrizes(): Collection
    {
        return $this->model->where('status', true)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUnusedCodes($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->where('status', false)
            ->pluck('code');
    }

    public function generateUniqueCode($gameId): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while ($this->existsByCode($code, $gameId));

        return $code;
    }
}