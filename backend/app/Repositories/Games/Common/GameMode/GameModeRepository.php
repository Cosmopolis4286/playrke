<?php

namespace App\Repositories\Games\Common\GameMode;

use App\Models\Games\Common\GameMode\GameMode;
use App\Repositories\BaseRepository;
use App\Contracts\Games\Common\GameMode\GameModeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class GameModeRepository extends BaseRepository implements GameModeRepositoryInterface
{
    public function __construct(GameMode $model)
    {
        $this->model = $model;
    }

    public function getByGame(string $gameId): Collection
    {
        return $this->model->where('game_id', $gameId)->orderBy('label')->get();
    }

    public function getActiveByGame(string $gameId): Collection
    {
        return $this->model->where('game_id', $gameId)->active()->orderBy('label')->get();
    }

    public function existsByGameAndCode(string $gameId, string $code, ?string $excludeId = null): bool
    {
        $query = $this->model->where('game_id', $gameId)->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
