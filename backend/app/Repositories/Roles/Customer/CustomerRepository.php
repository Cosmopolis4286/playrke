<?php

namespace App\Repositories\Roles\Customer;

use App\Contracts\Roles\Customer\CustomerRepositoryInterface;
use App\Models\Roles\Customer;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    public function __construct(Customer $model)
    {
        $this->model = $model;
    }

    public function existsByColumn(string $column, $value) : bool
    {
        return $this->model->where($column, $value)->exists();
    }

    public function existsCustomerById($id) : bool
    {
        return $this->model->where('id', $id)->exists();
    }

    public function generateUniqueCustomerKey() : string
    {
        do {
            $customerKey = bin2hex(random_bytes(32));
        } while ($this->existsByColumn('customer_key', $customerKey));

        return $customerKey;
    }

    public function getBlockedCustomers() : Collection
    {
        return $this->model->where('status', 'BLOCKED')->get();
    }

    public function getByCountry($countryId) : Collection
    {
        return $this->model->where('country_id', $countryId)->get();
    }

    public function getByCustomerKey(string $customerKey) : ?Customer
    {
        return $this->model->where('customer_key', $customerKey)->first();
    }

    public function getByUserId($userId) : ?Customer
    {
        return $this->model->where('user_id', $userId)->first();
    }

    public function getCustomersWithCommission(float $minCommission = 0) : Collection
    {
        return $this->model->where('commission', '>=', $minCommission)
            ->whereNotNull('commission')
            ->get();
    }

    public function getCustomersWithUser() : Collection
    {
        return $this->model->whereNotNull('user_id')->get();
    }

    public function getValueByCustomerKey(string $customerKey, string $column)
    {
        return $this->model->where('customer_key', $customerKey)->value($column);
    }

    public function searchByName(string $searchTerm) : Collection
    {
        return $this->model->where('name', 'LIKE', "%{$searchTerm}%")->get();
    }

    public function updateStatus($customerId, string $status) : bool
    {
        return $this->model->where('id', $customerId)
            ->update(['status' => $status]) > 0;
    }
}
