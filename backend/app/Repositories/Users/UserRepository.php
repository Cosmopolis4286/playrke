<?php

namespace App\Repositories\Users;

use App\Models\Users\User;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Users\Contracts\UserRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Repositorio para operaciones con usuarios
 * Maneja todas las consultas complejas relacionadas con User
 */
class UserRepository extends BaseRepository implements UserRepositoryContract
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Buscar usuario por email
     *
     * @param string $email Email del usuario
     * @param array $columns Columnas a seleccionar
     * @return User|null
     */
    public function findByEmail(string $email, array $columns = ['*']): ?User
    {
        return $this->model->where('email', $email)->first($columns);
    }

    /**
     * Buscar usuario por username
     *
     * @param string $username Username del usuario
     * @param array $columns Columnas a seleccionar
     * @return User|null
     */
    public function findByUsername(string $username, array $columns = ['*']): ?User
    {
        return $this->model->where('username', $username)->first($columns);
    }

    /**
     * Obtener usuarios demo (is_demo_user = true) con campos específicos
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDemoUsers(): \Illuminate\Support\Collection
    {
        return $this->model
            ->where('is_demo_user', true)
            ->get([
                'id',
                'email',
                'password',
                'first_name',
                'last_name',
            ])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'password' => $user->password,
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                    'role' => 'player', // Asumido fijo o puedes obtener del primer rol si quieres
                    'description' => 'Jugador de prueba',
                ];
            });
    }

    /**
     * Buscar usuario por código de referido
     *
     * @param string $referralCode Código de referido
     * @param array $columns Columnas a seleccionar
     * @return User|null
     */
    public function findByReferralCode(string $referralCode, array $columns = ['*']): ?User
    {
        return $this->model->where('referral_code', $referralCode)->first($columns);
    }

    /**
     * Genera un código de referido único para un usuario.
     *
     * @param int $length Longitud del código (default: 8)
     * @return string Código único
     */
    public function generateUniqueReferralCode(int $length = 8): string
    {
        do {
            // Genera un código alfanumérico en mayúsculas
            $code = Str::upper(Str::random($length));

            // Verifica si ya existe en la tabla users
            $exists = $this->model->where('referral_code', $code)->exists();
        } while ($exists);

        return $code;
    }

    /**
     * Obtener usuarios por estado
     *
     * @param string $status Estado del usuario
     * @param array $columns Columnas a seleccionar
     * @return Collection<User>
     */
    public function getUsersByStatus(string $status, array $columns = ['*']): Collection
    {
        return $this->model->where('status', $status)->get($columns);
    }

    /**
     * Obtener usuarios por país
     *
     * @param string $countryId ID del país
     * @param array $columns Columnas a seleccionar
     * @return Collection<User>
     */
    public function getUsersByCountry(string $countryId, array $columns = ['*']): Collection
    {
        return $this->model->where('country_id', $countryId)->get($columns);
    }

    /**
     * Obtener usuarios referidos por un usuario
     *
     * @param string $userId ID del usuario referidor
     * @param array $columns Columnas a seleccionar
     * @return Collection<User>
     */
    public function getReferredUsers(string $userId, array $columns = ['*']): Collection
    {
        return $this->model->where('referred_by_user_id', $userId)->get($columns);
    }

    /**
     * Obtener usuarios con rol específico
     *
     * @param string $roleName Nombre del rol
     * @param array $columns Columnas a seleccionar
     * @return Collection<User>
     */
    public function getUsersByRole(string $roleName, array $columns = ['*']): Collection
    {
        return $this->model->whereHas('roles', function ($query) use ($roleName) {
            $query->where('name', $roleName);
        })->get($columns);
    }

    /**
     * Verificar si existe usuario por email
     *
     * @param string $email Email a verificar
     * @return bool
     */
    public function existsByEmail(string $email): bool
    {
        return $this->model->where('email', $email)->exists();
    }

    /**
     * Verificar si existe usuario por username
     *
     * @param string $username Username a verificar
     * @return bool
     */
    public function existsByUsername(string $username): bool
    {
        return $this->model->where('username', $username)->exists();
    }

    /**
     * Verificar si existe código de referido
     *
     * @param string $referralCode Código a verificar
     * @return bool
     */
    public function existsByReferralCode(string $referralCode): bool
    {
        return $this->model->where('referral_code', $referralCode)->exists();
    }

    /**
     * Actualizar último login
     *
     * @param string $userId ID del usuario
     * @param string $ip IP del login
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
     * Obtener estadísticas de usuario
     *
     * @param string $userId ID del usuario
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
            'account_age_days' => $user->created_at->diffInDays(Carbon::now()),
        ];
    }

    /**
     * Buscar usuarios por múltiples criterios
     *
     * @param array $criteria Criterios de búsqueda
     * @return Collection<User>
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
     * Obtener usuarios activos recientes
     *
     * @param int $days Días de actividad reciente
     * @param int $limit Límite de resultados
     * @return Collection<User>
     */
    public function getRecentActiveUsers(int $days = 30, int $limit = 50): Collection
    {
        return $this->model->where('last_login_at', '>=', Carbon::now()->subDays($days))
            ->orderBy('last_login_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener usuarios con wallets activos
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<User>
     */
    public function getUsersWithActiveWallets(array $columns = ['*']): Collection
    {
        return $this->model->whereHas('wallets', function ($query) {
            $query->where('balance', '>', 0);
        })->get($columns);
    }

    /**
     * Contar referidos por usuario
     *
     * @param string $userId ID del usuario
     * @return int
     */
    public function countReferrals(string $userId): int
    {
        return $this->model->where('referred_by_user_id', $userId)->count();
    }

    /**
     * Verificar email del usuario
     *
     * @param string $userId ID del usuario
     * @return bool
     */
    public function verifyUserEmail(string $userId): bool
    {
        return $this->model->where('id', $userId)->update([
            'email_verified_at' => Carbon::now(),
            'status' => 'ACTIVE',
        ]) > 0;
    }
}
