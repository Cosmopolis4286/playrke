<?php

namespace App\Repositories\Users;

use App\Models\Users\User;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Users\Contracts\UserRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Repositorio para operaciones avanzadas relacionadas con usuarios.
 * Encapsula consultas complejas y lógica asociada al modelo User.
 */
class UserRepository extends BaseRepository implements UserRepositoryContract
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Buscar usuario por email.
     *
     * @param string $email
     * @param array $columns
     * @return User|null
     */
    public function findByEmail(string $email, array $columns = ['*']): ?User
    {
        return $this->model->where('email', $email)->first($columns);
    }

    /**
     * Buscar usuario por username.
     *
     * @param string $username
     * @param array $columns
     * @return User|null
     */
    public function findByUsername(string $username, array $columns = ['*']): ?User
    {
        return $this->model->where('username', $username)->first($columns);
    }

    /**
     * Obtener usuarios demo con datos internos (incluye password bcrypt).
     * Usado para procesos internos, NUNCA enviado al frontend.
     *
     * @return Collection
     */
    public function getDemoUsers(array $columns = ['*']): Collection
    {
        return $this->model
            ->where('is_demo_user', true)
            ->get($columns);
    }

    /**
     * Obtener usuarios demo formateados para ser enviados al frontend.
     * IMPORTANTE: Nunca envía passwords bcrypt del modelo.
     *
     * @return array
     */
    public function getDemoUsersForFrontend(): array
    {
        return $this->model
            ->where('is_demo_user', true)
            ->get()
            ->map(function ($user) {
                return [
                    'id'          => $user->id,
                    'email'       => $user->email,
                    'password'    => $user->demo_password ?? 'password123',
                    'firstName'   => $user->first_name,
                    'lastName'    => $user->last_name,
                    'role'        => $user->role ?? 'player',
                    'description' => $user->description ?? 'Usuario de prueba',
                ];
            })
            ->toArray();
    }

    /**
     * Buscar usuario por código de referido.
     *
     * @param string $referralCode
     * @param array $columns
     * @return User|null
     */
    public function findByReferralCode(string $referralCode, array $columns = ['*']): ?User
    {
        return $this->model->where('referral_code', $referralCode)->first($columns);
    }

    /**
     * Genera un código de referido único.
     *
     * @param int $length
     * @return string
     */
    public function generateUniqueReferralCode(int $length = 8): string
    {
        do {
            $code = Str::upper(Str::random($length));
        } while ($this->model->where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Obtener usuarios por estado.
     *
     * @param string $status
     * @param array $columns
     * @return Collection
     */
    public function getUsersByStatus(string $status, array $columns = ['*']): Collection
    {
        return $this->model->where('status', $status)->get($columns);
    }

    /**
     * Obtener usuarios por país.
     *
     * @param string $countryId
     * @param array $columns
     * @return Collection
     */
    public function getUsersByCountry(string $countryId, array $columns = ['*']): Collection
    {
        return $this->model->where('country_id', $countryId)->get($columns);
    }

    /**
     * Obtener usuarios referidos por otro usuario.
     *
     * @param string $userId
     * @param array $columns
     * @return Collection
     */
    public function getReferredUsers(string $userId, array $columns = ['*']): Collection
    {
        return $this->model->where('referred_by_user_id', $userId)->get($columns);
    }

    /**
     * Obtener usuarios con un rol específico.
     *
     * @param string $roleName
     * @param array $columns
     * @return Collection
     */
    public function getUsersByRole(string $roleName, array $columns = ['*']): Collection
    {
        return $this->model
            ->whereHas('roles', function ($query) use ($roleName) {
                $query->where('name', $roleName);
            })
            ->get($columns);
    }

    /**
     * Verificar si existe un email registrado.
     *
     * @param string $email
     * @return bool
     */
    public function existsByEmail(string $email): bool
    {
        return $this->model->where('email', $email)->exists();
    }

    /**
     * Verificar si existe un username registrado.
     *
     * @param string $username
     * @return bool
     */
    public function existsByUsername(string $username): bool
    {
        return $this->model->where('username', $username)->exists();
    }

    /**
     * Verificar si existe un código de referido.
     *
     * @param string $referralCode
     * @return bool
     */
    public function existsByReferralCode(string $referralCode): bool
    {
        return $this->model->where('referral_code', $referralCode)->exists();
    }

    /**
     * Actualizar el último login del usuario.
     *
     * @param string $userId
     * @param string $ip
     * @return bool
     */
    public function updateLastLogin(string $userId, string $ip): bool
    {
        return $this->model->where('id', $userId)->update([
            'last_login_at' => Carbon::now(),
            'last_login_ip' => $ip,
        ]) > 0;
    }

    /**
     * Obtener estadísticas del usuario.
     *
     * @param string $userId
     * @return array
     */
    public function getUserStats(string $userId): array
    {
        $user = $this->model->with(['referrals', 'wallets'])->find($userId);

        if (!$user) {
            return [
                'total_referrals' => 0,
                'active_wallets' => 0,
                'last_login' => null,
                'account_age_days' => 0,
            ];
        }

        return [
            'total_referrals' => $user->referrals->count(),
            'active_wallets' => $user->wallets->count(),
            'last_login' => $user->last_login_at,
            'account_age_days' => $user->created_at->diffInDays(now()),
        ];
    }

    /**
     * Buscar usuarios con criterios múltiples.
     *
     * @param array $criteria
     * @return Collection
     */
    public function searchUsers(array $criteria): Collection
    {
        $query = $this->model->query();

        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (isset($criteria['country_id'])) {
            $query->where('country_id', $criteria['country_id']);
        }

        if (isset($criteria['role'])) {
            $query->whereHas('roles', function ($q) use ($criteria) {
                $q->where('name', $criteria['role']);
            });
        }

        if (isset($criteria['search'])) {
            $search = $criteria['search'];

            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if (isset($criteria['created_from'])) {
            $query->where('created_at', '>=', $criteria['created_from']);
        }

        if (isset($criteria['created_to'])) {
            $query->where('created_at', '<=', $criteria['created_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtener usuarios activos recientemente.
     *
     * @param int $days
     * @param int $limit
     * @return Collection
     */
    public function getRecentActiveUsers(int $days = 30, int $limit = 50): Collection
    {
        return $this->model
            ->where('last_login_at', '>=', now()->subDays($days))
            ->orderBy('last_login_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener usuarios con wallets activos.
     *
     * @param array $columns
     * @return Collection
     */
    public function getUsersWithActiveWallets(array $columns = ['*']): Collection
    {
        return $this->model->whereHas('wallets', function ($query) {
            $query->where('balance', '>', 0);
        })->get($columns);
    }

    /**
     * Contar referidos de un usuario.
     *
     * @param string $userId
     * @return int
     */
    public function countReferrals(string $userId): int
    {
        return $this->model->where('referred_by_user_id', $userId)->count();
    }

    /**
     * Verificar el email del usuario.
     *
     * @param string $userId
     * @return bool
     */
    public function verifyUserEmail(string $userId): bool
    {
        return $this->model->where('id', $userId)->update([
            'email_verified_at' => now(),
            'status' => 'ACTIVE',
        ]) > 0;
    }
}
