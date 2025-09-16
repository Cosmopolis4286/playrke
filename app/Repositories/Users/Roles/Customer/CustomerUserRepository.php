<?php

namespace App\Repositories\Users\Roles\Customer;

use App\Models\Users\Roles\Customer\CustomerUser;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Users\Contracts\UserRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CustomerUserRepository extends BaseRepository implements CustomerUserRepositoryContract
{
    public function __construct(CustomerUser $model)
    {
        parent::__construct($model);
    }

    public function getByUserId($customerId, string $userId): ?CustomerUser
    {
        return $this->model->where('customer_id', $customerId)
            ->where('user_id', $userId)
            ->first();
    }

    public function getValueByUserId($customerId, string $userId, string $column)
    {
        return $this->model->where('customer_id', $customerId)
            ->where('user_id', $userId)
            ->value($column);
    }

    public function getByToken($customerId, string $token): ?CustomerUser
    {
        return $this->model->where('customer_id', $customerId)
            ->where('token', $token)
            ->first();
    }

    public function getValueByToken($customerId, string $token, string $column)
    {
        return $this->model->where('customer_id', $customerId)
            ->where('token', $token)
            ->value($column);
    }

    public function existsUserByCustomer($customerId, string $userId): bool
    {
        return $this->model->where('customer_id', $customerId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function getUsersByCustomer($customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)->get();
    }

    public function getCustomersByUserId(string $userId): Collection
    {
        return $this->model->where('user_id', $userId)->get();
    }

    public function getByCountryCode(string $countryCode): Collection
    {
        return $this->model->where('country_code', $countryCode)->get();
    }

    public function getByCurrency(string $currencyId): Collection
    {
        return $this->model->where('currency_id', $currencyId)->get();
    }

    public function getByCountryAndCurrency(string $countryCode, string $currencyId): Collection
    {
        return $this->model->where('country_code', $countryCode)
            ->where('currency_id', $currencyId)
            ->get();
    }

    public function updateLastActivity($customerId, string $userId): bool
    {
        return $this->model->where('customer_id', $customerId)
            ->where('user_id', $userId)
            ->update(['last_activity' => Carbon::now()]) > 0;
    }

    public function getRecentActivity($customerId, int $hours = 24): Collection
    {
        $since = Carbon::now()->subHours($hours);

        return $this->model->where('customer_id', $customerId)
            ->where('last_activity', '>=', $since)
            ->orderBy('last_activity', 'desc')
            ->get();
    }

    public function getInactiveUsers($customerId, int $days = 30): Collection
    {
        $cutoff = Carbon::now()->subDays($days);

        return $this->model->where('customer_id', $customerId)
            ->where(function($query) use ($cutoff) {
                $query->where('last_activity', '<', $cutoff)
                      ->orWhereNull('last_activity');
            })
            ->get();
    }

    public function searchByBrand(string $brand): Collection
    {
        return $this->model->where('brand', 'LIKE', "%{$brand}%")->get();
    }

    public function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while ($this->model->where('token', $token)->exists());

        return $token;
    }

    public function getActiveUsers($customerId): Collection
    {
        $recentActivity = Carbon::now()->subDays(7);

        return $this->model->where('customer_id', $customerId)
            ->where('last_activity', '>=', $recentActivity)
            ->get();
    }

    public function getUserActivity($customerId, string $userId): ?CustomerUser
    {
        return $this->model->select(['user_id', 'last_activity', 'created_at'])
            ->where('customer_id', $customerId)
            ->where('user_id', $userId)
            ->first();
    }

    public function bulkUpdateActivity(array $userIds, $customerId): int
    {
        return $this->model->where('customer_id', $customerId)
            ->whereIn('user_id', $userIds)
            ->update(['last_activity' => Carbon::now()]);
    }
}