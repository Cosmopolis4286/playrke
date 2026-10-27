<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Interface UserRepositoryInterface
 *
 * Define operaciones avanzadas para la gestión de usuarios.
 * Extiende BaseRepositoryInterface para los métodos CRUD base.
 */
interface UserRepositoryInterface extends BaseRepositoryInterface
{
    /* -----------------------------------------------------------------
     | Verificación de existencia
     ----------------------------------------------------------------- */

    /**
     * Verificar existencia por email.
     */
    public function existsByEmail(string $email) : bool;

    /**
     * Verificar existencia por código de referido.
     */
    public function existsByReferralCode(string $referralCode) : bool;

    /**
     * Verificar existencia por username.
     */
    public function existsByUsername(string $username) : bool;
    /* -----------------------------------------------------------------
     | Búsquedas básicas
     ----------------------------------------------------------------- */

    /**
     * Buscar un usuario por email.
     */
    public function findByEmail(string $email, array $columns = ['*']) : ?User;

    /**
     * Buscar un usuario por su código de referido.
     */
    public function findByReferralCode(string $referralCode, array $columns = ['*']) : ?User;

    /**
     * Buscar un usuario por username.
     */
    public function findByUsername(string $username, array $columns = ['*']) : ?User;

    /**
     * Generar un código de referido único.
     */
    public function generateUniqueReferralCode(int $length = 8) : string;

    /* -----------------------------------------------------------------
     | Usuarios Demo
     ----------------------------------------------------------------- */

    /**
     * Obtener usuarios con flag is_demo_user = true.
     *
     * @return Collection<User>
     */
    public function getDemoUsers(array $columns = ['*']) : Collection;

    /**
     * Obtener usuarios demo formateados para frontend.
     */
    public function getDemoUsersForFrontend() : SupportCollection;

    /**
     * Obtener usuarios con actividad reciente.
     *
     * @return Collection<User>
     */
    public function getRecentActiveUsers(int $days = 30, int $limit = 50) : Collection;

    /**
     * Obtener usuarios referidos por un usuario.
     *
     * @return Collection<User>
     */
    public function getReferredUsers(string $userId, array $columns = ['*']) : Collection;

    /**
     * Obtener usuarios filtrados por país.
     *
     * @return Collection<User>
     */
    public function getUsersByCountry(string $countryId, array $columns = ['*']) : Collection;

    /**
     * Obtener usuarios por rol.
     *
     * @return Collection<User>
     */
    public function getUsersByRole(string $roleName, array $columns = ['*']) : Collection;

    /* -----------------------------------------------------------------
     | Filtros específicos
     ----------------------------------------------------------------- */

    /**
     * Obtener usuarios filtrados por estado.
     *
     * @return Collection<User>
     */
    public function getUsersByStatus(string $status, array $columns = ['*']) : Collection;

    /**
     * Obtener estadísticas personalizadas del usuario.
     */
    public function getUserStats(string $userId) : array;

    /**
     * Buscar usuarios con múltiples criterios dinámicos.
     *
     * @return Collection<User>
     */
    public function searchUsers(array $criteria) : Collection;

    /* -----------------------------------------------------------------
     | Utilidades
     ----------------------------------------------------------------- */

    /**
     * Actualizar datos de último inicio de sesión.
     */
    public function updateLastLogin(string $userId, string $ip) : bool;
}
