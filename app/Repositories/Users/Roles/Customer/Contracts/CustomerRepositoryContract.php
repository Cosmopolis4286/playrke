<?php

namespace App\Repositories\Users\Roles\Customer\Contracts;

use App\Repositories\Contracts\BaseRepositoryContract;
use App\Models\Users\Roles\Customer\Customer;
use Illuminate\Database\Eloquent\Collection;

interface CustomerRepositoryContract extends BaseRepositoryContract
{
    public function getByUserId($userId): ?Customer;

    public function getByCustomerCode(string $customerCode): ?Customer;

    public function getByToken(string $token): ?Customer;

    public function existsCustomerById($id): bool;

    public function existsByColumn(string $column, $value): bool;

    public function getValueByUserId($userId, string $column);

    public function getValueByToken(string $token, string $column);

    public function getValueByCustomerCode(string $customerCode, string $column);

    public function getActiveCustomers(): Collection;

    public function getInactiveCustomers(): Collection;

    public function getBlockedCustomers(): Collection;

    public function getByCountry($countryId): Collection;

    public function getCustomersWithUser(): Collection;

    public function searchByName(string $searchTerm): Collection;

    public function updateStatus($customerId, string $status): bool;

    public function generateUniqueToken(): string;

    public function getCustomersWithCommission(float $minCommission = 0): Collection;

    public function getAllInCustomers(): Collection;
}