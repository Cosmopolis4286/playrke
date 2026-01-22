<?php

namespace App\Contracts\Roles\Customer;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Roles\CustomerUser;
use Illuminate\Database\Eloquent\Collection;

interface CustomerUserRepositoryInterface extends BaseRepositoryInterface
{
    public function getByUserId($customerId, string $userId): ?CustomerUser;

    public function getByToken($customerId, string $token): ?CustomerUser;

    public function getValueByToken($customerId, string $token, string $column);

    public function existsUserByCustomer($customerId, string $userId): bool;

    public function getUsersByCustomer($customerId): Collection;

    public function getCustomersByUserId(string $userId): Collection;

    public function getByCountryCode(string $countryCode): Collection;

    public function getByCurrency(string $currencyId): Collection;

    public function getByCountryAndCurrency(string $countryCode, string $currencyId): Collection;

    public function updateLastActivity($customerId, string $userId): bool;

    public function getRecentActivity($customerId, int $hours = 24): Collection;

    public function getInactiveUsers($customerId, int $days = 30): Collection;

    public function searchByBrand(string $brand): Collection;

    public function generateUniqueToken(): string;

    public function getActiveUsers($customerId): Collection;

    public function getUserActivity($customerId, string $userId): ?CustomerUser;

    public function bulkUpdateActivity(array $userIds, $customerId): int;
}
