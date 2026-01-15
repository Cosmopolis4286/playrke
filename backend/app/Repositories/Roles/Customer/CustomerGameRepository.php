<?php

namespace App\Repositories\Roles\Customer;

use App\Models\Roles\Customer\CustomerGame;
use App\Repositories\BaseRepository;
use App\Contracts\Roles\Customer\CustomerGameRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CustomerGameRepository extends BaseRepository implements CustomerGameRepositoryInterface
{
    public function __construct(CustomerGame $model)
    {
        parent::__construct($model);
    }

    public function getByGameID($customerId, $gameId): ?CustomerGame
    {
        return $this->model->where('customer_id', $customerId)
            ->where('game_id', $gameId)
            ->first();
    }

    public function getValueByCustomerGameID($customerId, $gameId, string $column = 'customer_game_id')
    {
        return $this->model->where('customer_id', $customerId)
            ->where('game_id', $gameId)
            ->value($column);
    }

    public function getGamesByCustomer($customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)->get();
    }

    public function getCustomersByGame($gameId): Collection
    {
        return $this->model->where('game_id', $gameId)->get();
    }

    public function getActiveCustomerGame($customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)
            ->where('status', 'ACTIVE')
            ->get();
    }

    public function getInactiveCustomerGame($customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)
            ->where('status', 'INACTIVE')
            ->get();
    }

    public function getBlockedCustomerGame($customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)
            ->where('status', 'BLOCKED')
            ->get();
    }

    public function existsCustomerGame($customerId, $gameId): bool
    {
        return $this->model->where('customer_id', $customerId)
            ->where('game_id', $gameId)
            ->exists();
    }

    public function getByCustomerGameId(string $customerGameId): ?CustomerGame
    {
        return $this->model->where('customer_game_id', $customerGameId)->first();
    }

    public function searchByProduct(string $product): Collection
    {
        return $this->model->where('product', 'LIKE', "%{$product}%")->get();
    }

    public function updateStatus($customerId, $gameId, string $status): bool
    {
        return $this->model->where('customer_id', $customerId)
            ->where('game_id', $gameId)
            ->update(['status' => $status]) > 0;
    }

    public function getCustomersWithGame($gameId, string $status = 'ACTIVE'): Collection
    {
        return $this->model->where('game_id', $gameId)
            ->where('status', $status)
            ->with('customer')
            ->get();
    }

    public function getGamesWithCustomer($customerId, string $status = 'ACTIVE'): Collection
    {
        return $this->model->where('customer_id', $customerId)
            ->where('status', $status)
            ->with('game')
            ->get();
    }

    public function createCustomerGame($customerId, $gameId, string $customerGameId, string $product, ?string $description = null): CustomerGame
    {
        return $this->model->create([
            'customer_id' => $customerId,
            'game_id' => $gameId,
            'customer_game_id' => $customerGameId,
            'product' => $product,
            'description' => $description,
            'status' => 'ACTIVE'
        ]);
    }

    public function getActiveGamesByProduct(string $product): Collection
    {
        return $this->model->where('product', $product)
            ->where('status', 'ACTIVE')
            ->get();
    }

    public function getCustomerGameStatistics($customerId): array
    {
        $total = $this->model->where('customer_id', $customerId)->count();
        $active = $this->model->where('customer_id', $customerId)
            ->where('status', 'ACTIVE')->count();
        $inactive = $this->model->where('customer_id', $customerId)
            ->where('status', 'INACTIVE')->count();
        $blocked = $this->model->where('customer_id', $customerId)
            ->where('status', 'BLOCKED')->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'blocked' => $blocked
        ];
    }
}