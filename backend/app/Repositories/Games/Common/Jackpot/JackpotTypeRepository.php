<?php

namespace App\Repositories\Games\Common\Jackpot;

use App\Models\Games\Common\Jackpot\JackpotType;
use App\Repositories\BaseRepository;
use App\Contracts\Games\Common\Jackpot\JackpotTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class JackpotTypeRepository extends BaseRepository implements JackpotTypeRepositoryInterface
{
    public function __construct(JackpotType $model)
    {
        $this->model = $model;
    }

    public function getActiveTypes(): Collection
    {
        return $this->model->active()->orderBy('label')->get();
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

    /**
     * Override: BaseRepository::delete() intenta `status = 'DELETED'` antes
     * del borrado físico, pero el enum de esta tabla solo admite
     * ACTIVE/INACTIVE (mismo shape que prize_pool_types) — un borrado
     * directo es correcto aquí, JackpotTypeService::delete() ya valida que
     * no esté en uso antes de llegar a este punto.
     */
    public function delete(string|int $id): bool
    {
        $model = $this->find($id);

        return $model ? (bool) $model->delete() : false;
    }
}
