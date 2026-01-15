<?php

namespace App\Repositories\Users;

use App\Models\User;
use App\Repositories\BaseRepository;
use App\Repositories\Users\Contracts\UserRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserRepository extends BaseRepository implements UserRepositoryContract
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /* -----------------------------------------------------------------
     | Búsquedas básicas
     ----------------------------------------------------------------- */

    public function findByEmail(string $email, array $columns = ['*']): ?User
    {
        return $this->model
            ->select($columns)
            ->where('email', $email)
            ->first();
    }

    public function findByUsername(string $username, array $columns = ['*']): ?User
    {
        return $this->model
            ->select($columns)
            ->where('username', $username)
            ->first();
    }

    public function findByReferralCode(string $referralCode, array $columns = ['*']): ?User
    {
        return $this->model
            ->select($columns)
            ->where('referral_code', $referralCode)
            ->first();
    }

    /* -----------------------------------------------------------------
     | Usuarios Demo
     ----------------------------------------------------------------- */

    public function getDemoUsers(array $columns = ['*']): Collection
    {
        return $this->model
            ->select($columns)
            ->where('is_demo_user', true)
            ->get();
    }

    public function getDemoUsersForFrontend(): SupportCollection
    {
        return $this->model
            ->with('roles')
            ->where('is_demo_user', true)
            ->get()
            ->map(function (User $user) {
                // Obtener el primer rol del usuario o usar 'player' por defecto
                $roleName = $user->roles->first()?->name ?? 'player';

                return [
                    'id'          => $user->id,
                    'email'       => $user->email,
                    'password'    => $user->demo_password ?? 'password123',
                    'firstName'   => $user->first_name,
                    'lastName'    => $user->last_name,
                    'role'        => $roleName,
                    'description' => $user->description ?? 'Usuario de prueba',
                ];
            });
    }

    /* -----------------------------------------------------------------
     | Filtros directos
     ----------------------------------------------------------------- */

    public function getUsersByStatus(string $status, array $columns = ['*']): Collection
    {
        return $this->model->select($columns)->where('status', $status)->get();
    }

    public function getUsersByCountry(string $countryId, array $columns = ['*']): Collection
    {
        return $this->model->select($columns)->where('country_id', $countryId)->get();
    }

    public function getReferredUsers(string $userId, array $columns = ['*']): Collection
    {
        return $this->model->select($columns)->where('referred_by_user_id', $userId)->get();
    }

    public function getUsersByRole(string $roleName, array $columns = ['*']): Collection
    {
        return $this->model
            ->select($columns)
            ->whereHas('roles', fn($q) => $q->where('name', $roleName))
            ->get();
    }

    /* -----------------------------------------------------------------
     | Verificaciones de existencia
     ----------------------------------------------------------------- */

    public function existsByEmail(string $email): bool
    {
        return $this->model->where('email', $email)->exists();
    }

    public function existsByUsername(string $username): bool
    {
        return $this->model->where('username', $username)->exists();
    }

    public function existsByReferralCode(string $referralCode): bool
    {
        return $this->model->where('referral_code', $referralCode)->exists();
    }

    /* -----------------------------------------------------------------
     | Utilidades
     ----------------------------------------------------------------- */

    public function updateLastLogin(string $userId, string $ip): bool
    {
        return $this->model
            ->where('id', $userId)
            ->update([
                'last_login_at' => now(),
                'last_login_ip' => $ip,
            ]) > 0;
    }

    public function getUserStats(string $userId): array
    {
        $user = $this->model
            ->with(['referrals', 'wallets'])
            ->find($userId);

        if (!$user) {
            return [
                'total_referrals'   => 0,
                'active_wallets'    => 0,
                'last_login'        => null,
                'account_age_days'  => 0,
            ];
        }

        return [
            'total_referrals'   => $user->referrals->count(),
            'active_wallets'    => $user->wallets->count(),
            'last_login'        => $user->last_login_at,
            'account_age_days'  => $user->created_at->diffInDays(now()),
        ];
    }

    public function searchUsers(array $criteria): Collection
    {
        $query = $this->model->query();

        $query->when($criteria['status'] ?? null, function ($q, $value) {
            $q->where('status', $value);
        });

        $query->when($criteria['country_id'] ?? null, function ($q, $value) {
            $q->where('country_id', $value);
        });

        $query->when($criteria['role'] ?? null, function ($q, $value) {
            $q->whereHas('roles', fn($r) => $r->where('name', $value));
        });

        $query->when($criteria['search'] ?? null, function ($q, $value) {
            $q->where(function ($inner) use ($value) {
                $inner->where('username', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%")
                    ->orWhere('first_name', 'like', "%{$value}%")
                    ->orWhere('last_name', 'like', "%{$value}%");
            });
        });

        $query->when($criteria['created_from'] ?? null, fn($q, $value) => $q->where('created_at', '>=', $value));
        $query->when($criteria['created_to'] ?? null, fn($q, $value) => $q->where('created_at', '<=', $value));

        return $query->orderByDesc('created_at')->get();
    }

    public function getRecentActiveUsers(int $days = 30, int $limit = 50): Collection
    {
        return $this->model
            ->where('last_login_at', '>=', now()->subDays($days))
            ->orderByDesc('last_login_at')
            ->limit($limit)
            ->get();
    }

    /* -----------------------------------------------------------------
     | Extras útiles
     ----------------------------------------------------------------- */

    public function getUsersWithActiveWallets(array $columns = ['*']): Collection
    {
        return $this->model
            ->select($columns)
            ->whereHas('wallets', fn($q) => $q->where('balance', '>', 0))
            ->get();
    }

    public function countReferrals(string $userId): int
    {
        return $this->model->where('referred_by_user_id', $userId)->count();
    }

    public function verifyUserEmail(string $userId): bool
    {
        return $this->model
            ->where('id', $userId)
            ->update([
                'email_verified_at' => now(),
                'status' => 'ACTIVE',
            ]) > 0;
    }

    /* -----------------------------------------------------------------
     | Generación de Códigos
     ----------------------------------------------------------------- */

    public function generateUniqueReferralCode(int $length = 8): string
    {
        do {
            $code = Str::upper(Str::random($length));
        } while ($this->existsByReferralCode($code));

        return $code;
    }
}
