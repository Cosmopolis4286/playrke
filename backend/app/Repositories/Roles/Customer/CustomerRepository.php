<?php

namespace App\Repositories\Roles\Customer;

use App\Models\Roles\Customer;
use App\Repositories\BaseRepository;
use App\Contracts\Roles\Customer\CustomerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    public function __construct(Customer $model)
    {
        $this->model = $model;
    }

    public function getByUserId($userId): ?Customer
    {
        return $this->model->where('user_id', $userId)->first();
    }

    public function getByCustomerCode(string $customerCode): ?Customer
    {
        return $this->model->where('customer_code', $customerCode)->first();
    }

    public function getByToken(string $token): ?Customer
    {
        return $this->model->where('token', $token)->first();
    }

    public function existsCustomerById($id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    public function existsByColumn(string $column, $value): bool
    {
        return $this->model->where($column, $value)->exists();
    }

    public function getValueByToken(string $token, string $column)
    {
        return $this->model->where('token', $token)->value($column);
    }

    public function getValueByCustomerCode(string $customerCode, string $column)
    {
        return $this->model->where('customer_code', $customerCode)->value($column);
    }

    public function getBlockedCustomers(): Collection
    {
        return $this->model->where('status', 'BLOCKED')->get();
    }

    public function getByCountry($countryId): Collection
    {
        return $this->model->where('country_id', $countryId)->get();
    }

    public function getCustomersWithUser(): Collection
    {
        return $this->model->whereNotNull('user_id')->get();
    }

    public function searchByName(string $searchTerm): Collection
    {
        return $this->model->where('name', 'LIKE', "%{$searchTerm}%")->get();
    }

    public function updateStatus($customerId, string $status): bool
    {
        return $this->model->where('id', $customerId)
            ->update(['status' => $status]) > 0;
    }

    public function generateUniqueToken(): string
    {
        do {
            $token = Str::uuid()->toString();
        } while ($this->existsByColumn('token', $token));

        return $token;
    }

    public function getCustomersWithCommission(float $minCommission = 0): Collection
    {
        return $this->model->where('commission', '>=', $minCommission)
            ->whereNotNull('commission')
            ->get();
    }

    public function getAllInCustomers(): Collection
    {
        return $this->model->where('all_in', true)->get();
    }
}
