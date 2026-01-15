<?php

namespace App\Repositories\Users\Contracts;

use App\Models\User;
use App\Repositories\Base\Contracts\BaseRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Interface UserRepositoryContract
 *
 * Define operaciones avanzadas para la gestión de usuarios.
 * Extiende BaseRepositoryContract para los métodos CRUD base.
 */
interface UserRepositoryContract extends BaseRepositoryContract
{
    /* -----------------------------------------------------------------
     | Búsquedas básicas
     ----------------------------------------------------------------- */

    /**
     * Buscar un usuario por email.
     *
     * @param string $email
     * @param array  $columns
     * @return User|null
     */
    public function findByEmail(string $email, array $columns = ['*']): ?User;

    /**
     * Buscar un usuario por username.
     *
     * @param string $username
     * @param array  $columns
     * @return User|null
     */
    public function findByUsername(string $username, array $columns = ['*']): ?User;

    /**
     * Buscar un usuario por su código de referido.
     *
     * @param string $referralCode
     * @param array  $columns
     * @return User|null
     */
    public function findByReferralCode(string $referralCode, array $columns = ['*']): ?User;


    /* -----------------------------------------------------------------
     | Usuarios Demo
     ----------------------------------------------------------------- */

    /**
     * Obtener usuarios con flag is_demo_user = true.
     *
     * @param array $columns
     * @return Collection<User>
     */
    public function getDemoUsers(array $columns = ['*']): Collection;

    /**
     * Obtener usuarios demo formateados para frontend.
     *
     * @return SupportCollection
     */
    public function getDemoUsersForFrontend(): SupportCollection;


    /* -----------------------------------------------------------------
     | Filtros específicos
     ----------------------------------------------------------------- */

    /**
     * Obtener usuarios filtrados por estado.
     *
     * @param string $status
     * @param array  $columns
     * @return Collection<User>
     */
    public function getUsersByStatus(string $status, array $columns = ['*']): Collection;

    /**
     * Obtener usuarios filtrados por país.
     *
     * @param string $countryId
     * @param array  $columns
     * @return Collection<User>
     */
    public function getUsersByCountry(string $countryId, array $columns = ['*']): Collection;

    /**
     * Obtener usuarios referidos por un usuario.
     *
     * @param string $userId
     * @param array  $columns
     * @return Collection<User>
     */
    public function getReferredUsers(string $userId, array $columns = ['*']): Collection;

    /**
     * Obtener usuarios por rol.
     *
     * @param string $roleName
     * @param array  $columns
     * @return Collection<User>
     */
    public function getUsersByRole(string $roleName, array $columns = ['*']): Collection;


    /* -----------------------------------------------------------------
     | Verificación de existencia
     ----------------------------------------------------------------- */

    /**
     * Verificar existencia por email.
     *
     * @param string $email
     * @return bool
     */
    public function existsByEmail(string $email): bool;

    /**
     * Verificar existencia por username.
     *
     * @param string $username
     * @return bool
     */
    public function existsByUsername(string $username): bool;

    /**
     * Verificar existencia por código de referido.
     *
     * @param string $referralCode
     * @return bool
     */
    public function existsByReferralCode(string $referralCode): bool;


    /* -----------------------------------------------------------------
     | Utilidades
     ----------------------------------------------------------------- */

    /**
     * Actualizar datos de último inicio de sesión.
     *
     * @param string $userId
     * @param string $ip
     * @return bool
     */
    public function updateLastLogin(string $userId, string $ip): bool;

    /**
     * Obtener estadísticas personalizadas del usuario.
     *
     * @param string $userId
     * @return array
     */
    public function getUserStats(string $userId): array;

    /**
     * Buscar usuarios con múltiples criterios dinámicos.
     *
     * @param array $criteria
     * @return Collection<User>
     */
    public function searchUsers(array $criteria): Collection;

    /**
     * Obtener usuarios con actividad reciente.
     *
     * @param int $days
     * @param int $limit
     * @return Collection<User>
     */
    public function getRecentActiveUsers(int $days = 30, int $limit = 50): Collection;

    /**
     * Generar un código de referido único.
     *
     * @param int $length
     * @return string
     */
    public function generateUniqueReferralCode(int $length = 8): string;
}
