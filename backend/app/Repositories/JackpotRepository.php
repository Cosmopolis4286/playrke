<?php

namespace App\Repositories;

use App\Contracts\JackpotRepositoryInterface;
use App\Models\Jackpot;

class JackpotRepository extends BaseRepository implements JackpotRepositoryInterface
{
    public function __construct(Jackpot $model)
    {
        $this->model = $model;
    }

    public function findByType(string $jackpotTypeId) : ?Jackpot
    {
        return $this->model->where('jackpot_type_id', $jackpotTypeId)->first();
    }

    public function firstOrCreateByType(string $jackpotTypeId) : Jackpot
    {
        return $this->model->firstOrCreate(
            ['jackpot_type_id' => $jackpotTypeId],
            ['amount' => 0],
        );
    }

    public function incrementAmount(string $jackpotId, float $amount) : bool
    {
        return $this->model->where('id', $jackpotId)
            ->increment('amount', $amount) > 0;
    }
}
