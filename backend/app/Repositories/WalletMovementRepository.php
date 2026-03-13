<?php

namespace App\Repositories;

use App\Contracts\WalletMovementRepositoryInterface;
use App\Models\WalletMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class WalletMovementRepository extends BaseRepository implements WalletMovementRepositoryInterface
{
    public function __construct(WalletMovement $model)
    {
        $this->model = $model;
    }

    public function record(array $data): WalletMovement
    {
        return $this->model->create($data);
    }

    public function getByWallet(string $walletId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model
            ->where('wallet_id', $walletId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getByUser(string $userId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getLatestByWallet(string $walletId, int $limit = 10): Collection
    {
        return $this->model
            ->where('wallet_id', $walletId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
