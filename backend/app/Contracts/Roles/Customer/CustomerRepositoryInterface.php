<?php

namespace App\Contracts\Roles\Customer;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Roles\Customer;
use Illuminate\Database\Eloquent\Collection;

interface CustomerRepositoryInterface extends BaseRepositoryInterface
{
    public function existsByColumn(string $column, $value) : bool;

    public function existsCustomerById($id) : bool;

    public function generateUniqueCustomerKey() : string;

    public function getBlockedCustomers() : Collection;

    public function getByCountry($countryId) : Collection;

    public function getByCustomerKey(string $customerKey) : ?Customer;

    public function getByUserId($userId) : ?Customer;

    public function getCustomersWithCommission(float $minCommission = 0) : Collection;

    public function getCustomersWithUser() : Collection;

    public function getValueByCustomerKey(string $customerKey, string $column);

    public function searchByName(string $searchTerm) : Collection;

    public function updateStatus($customerId, string $status) : bool;
}
