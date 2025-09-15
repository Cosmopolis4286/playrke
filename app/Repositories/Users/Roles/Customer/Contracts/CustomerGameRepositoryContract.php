<?php

namespace App\Repositories\Users\Roles\Customer\Contracts;

use App\Repositories\Contracts\BaseRepositoryContract;
use App\Models\Users\Roles\Customer\CustomerGame;
use Illuminate\Database\Eloquent\Collection;

interface CustomerGameRepositoryContract extends BaseRepositoryContract
{
    public function getByGameID($customerId, $gameId): ?CustomerGame;

    public function getValueByCustomerGameID($customerId, $gameId, string $column = 'customer_game_id');

    public function getGamesByCustomer($customerId): Collection;

    public function getCustomersByGame($gameId): Collection;

    public function getActiveCustomerGame($customerId): Collection;

    public function getInactiveCustomerGame($customerId): Collection;

    public function getBlockedCustomerGame($customerId): Collection;

    public function existsCustomerGame($customerId, $gameId): bool;

    public function getByCustomerGameId(string $customerGameId): ?CustomerGame;

    public function searchByProduct(string $product): Collection;

    public function updateStatus($customerId, $gameId, string $status): bool;

    public function getCustomersWithGame($gameId, string $status = 'ACTIVE'): Collection;

    public function getGamesWithCustomer($customerId, string $status = 'ACTIVE'): Collection;

    public function createCustomerGame($customerId, $gameId, string $customerGameId, string $product, ?string $description = null): CustomerGame;

    public function getActiveGamesByProduct(string $product): Collection;

    public function getCustomerGameStatistics($customerId): array;
}