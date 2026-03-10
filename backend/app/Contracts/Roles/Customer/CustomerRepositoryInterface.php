<?php

namespace App\Contracts\Roles\Customer;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Roles\Customer;
use Illuminate\Database\Eloquent\Collection;

interface CustomerRepositoryInterface extends BaseRepositoryInterface
{
    public function getByUserId($userId): ?Customer;

    public function getByCustomerKey(string $customerKey): ?Customer;

    public function existsCustomerById($id): bool;

    public function existsByColumn(string $column, $value): bool;

    public function getValueByCustomerKey(string $customerKey, string $column);

    public function getBlockedCustomers(): Collection;

    public function getByCountry($countryId): Collection;

    public function getCustomersWithUser(): Collection;

    public function searchByName(string $searchTerm): Collection;

    public function updateStatus($customerId, string $status): bool;

    public function generateUniqueCustomerKey(): string;

    public function getCustomersWithCommission(float $minCommission = 0): Collection;
}
