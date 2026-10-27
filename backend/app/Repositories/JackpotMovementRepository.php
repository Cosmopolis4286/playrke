<?php

namespace App\Repositories;

use App\Contracts\JackpotMovementRepositoryInterface;
use App\Models\JackpotMovement;
use Illuminate\Database\Eloquent\Collection;

class JackpotMovementRepository extends BaseRepository implements JackpotMovementRepositoryInterface
{
    public function __construct(JackpotMovement $model)
    {
        $this->model = $model;
    }

    public function getByJackpot(string $jackpotId, int $limit = 20) : Collection
    {
        return $this->model
            ->where('jackpot_id', $jackpotId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function record(array $data) : JackpotMovement
    {
        return $this->model->create($data);
    }
}
