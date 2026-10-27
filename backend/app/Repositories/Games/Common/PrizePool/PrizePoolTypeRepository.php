<?php

namespace App\Repositories\Games\Common\PrizePool;

use App\Contracts\Games\Common\PrizePool\PrizePoolTypeRepositoryInterface;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class PrizePoolTypeRepository extends BaseRepository implements PrizePoolTypeRepositoryInterface
{
    public function __construct(PrizePoolType $model)
    {
        $this->model = $model;
    }

    public function existsByGameAndCode(string $gameId, string $code, ?string $excludeId = null) : bool
    {
        $query = $this->model->where('game_id', $gameId)->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function getActiveByGame(string $gameId) : Collection
    {
        return $this->model->where('game_id', $gameId)->active()->orderBy('label')->get();
    }

    public function getActiveTypes() : Collection
    {
        return $this->model->active()->orderBy('label')->get();
    }

    public function getByGame(string $gameId) : Collection
    {
        return $this->model->where('game_id', $gameId)->orderBy('label')->get();
    }
}
