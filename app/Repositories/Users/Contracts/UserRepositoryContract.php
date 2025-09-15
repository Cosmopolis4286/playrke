<?php

namespace App\Repositories\Users\Contracts;

use App\Models\Users\User;
use App\Repositories\Contracts\BaseRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato específico para el repositorio de usuarios
 * Define métodos específicos para operaciones con User
 */
interface UserRepositoryContract extends BaseRepositoryContract
{
    /**
     * Buscar usuario por email
     *
     * @param string $email Email del usuario
     * @param array $columns Columnas a seleccionar
     * @return User|null
     */
    public function findByEmail(string $email, array $columns = ['*']): ?User;

    /**
     * Buscar usuario por username
     *
     * @param string $username Username del usuario
     * @param array $columns Columnas a seleccionar
     * @return User|null
     */
    public function findByUsername(string $username, array $columns = ['*']): ?User;

    /**
     * Buscar usuario por código de referido
     *
     * @param string $referralCode Código de referido
     * @param array $columns Columnas a seleccionar
     * @return User|null
     */
    public function findByReferralCode(string $referralCode, array $columns = ['*']): ?User;

    /**
     * Obtener usuarios por estado
     *
     * @param string $status Estado del usuario
     * @param array $columns Columnas a seleccionar
     * @return Collection<User>
     */
    public function getUsersByStatus(string $status, array $columns = ['*']): Collection;

    /**
     * Obtener usuarios por país
     *
     * @param string $countryId ID del país
     * @param array $columns Columnas a seleccionar
     * @return Collection<User>
     */
    public function getUsersByCountry(string $countryId, array $columns = ['*']): Collection;

    /**
     * Obtener usuarios referidos por un usuario
     *
     * @param string $userId ID del usuario referidor
     * @param array $columns Columnas a seleccionar
     * @return Collection<User>
     */
    public function getReferredUsers(string $userId, array $columns = ['*']): Collection;

    /**
     * Obtener usuarios con rol específico
     *
     * @param string $roleName Nombre del rol
     * @param array $columns Columnas a seleccionar
     * @return Collection<User>
     */
    public function getUsersByRole(string $roleName, array $columns = ['*']): Collection;

    /**
     * Verificar si existe usuario por email
     *
     * @param string $email Email a verificar
     * @return bool
     */
    public function existsByEmail(string $email): bool;

    /**
     * Verificar si existe usuario por username
     *
     * @param string $username Username a verificar
     * @return bool
     */
    public function existsByUsername(string $username): bool;

    /**
     * Verificar si existe código de referido
     *
     * @param string $referralCode Código a verificar
     * @return bool
     */
    public function existsByReferralCode(string $referralCode): bool;

    /**
     * Actualizar último login
     *
     * @param string $userId ID del usuario
     * @param string $ip IP del login
     * @return bool
     */
    public function updateLastLogin(string $userId, string $ip): bool;

    /**
     * Obtener estadísticas de usuario
     *
     * @param string $userId ID del usuario
     * @return array
     */
    public function getUserStats(string $userId): array;

    /**
     * Buscar usuarios por múltiples criterios
     *
     * @param array $criteria Criterios de búsqueda
     * @return Collection<User>
     */
    public function searchUsers(array $criteria): Collection;

    /**
     * Obtener usuarios activos recientes
     *
     * @param int $days Días de actividad reciente
     * @param int $limit Límite de resultados
     * @return Collection<User>
     */
    public function getRecentActiveUsers(int $days = 30, int $limit = 50): Collection;
}