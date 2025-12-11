<?php

namespace App\Repositories\Users\Contracts;

use App\Models\Users\User;
use App\Repositories\Base\Contracts\BaseRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface UserRepositoryContract
 *
 * Define las operaciones avanzadas disponibles para el manejo de usuarios.
 * Extiende BaseRepositoryContract para incluir métodos CRUD estándar.
 */
interface UserRepositoryContract extends BaseRepositoryContract
{
    /* -----------------------------------------------------------------
     | Búsquedas básicas
     ----------------------------------------------------------------- */

    /**
     * Buscar un usuario por su email.
     *
     * @param string $email   Email del usuario.
     * @param array  $columns Columnas a seleccionar.
     *
     * @return User|null
     */
    public function findByEmail(string $email, array $columns = ['*']): ?User;

    /**
     * Buscar un usuario por su username.
     *
     * @param string $username Username del usuario.
     * @param array  $columns  Columnas a seleccionar.
     *
     * @return User|null
     */
    public function findByUsername(string $username, array $columns = ['*']): ?User;

    /**
     * Buscar un usuario por su código de referido.
     *
     * @param string $referralCode Código de referido.
     * @param array  $columns      Columnas a seleccionar.
     *
     * @return User|null
     */
    public function findByReferralCode(string $referralCode, array $columns = ['*']): ?User;


    /* -----------------------------------------------------------------
     | Usuarios Demo
     ----------------------------------------------------------------- */

    /**
     * Obtener usuarios cuyo flag is_demo_user = true.
     *
     * @param array $columns Columnas a seleccionar.
     *
     * @return Collection<User>
     */
    public function getDemoUsers(array $columns = ['*']): Collection;

    /**
     * Obtener usuarios demo formateados para uso en frontend.
     *
     * Ejemplo de retorno esperado:
     * [
     *     [
     *         'id'          => 'player-1',
     *         'email'       => 'player@test.com',
     *         'password'    => 'password123',
     *         'firstName'   => 'Juan',
     *         'lastName'    => 'Pérez',
     *         'role'        => 'player',
     *         'description' => 'Jugador de prueba'
     *     ]
     * ]
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDemoUsersForFrontend(): \Illuminate\Support\Collection;


    /* -----------------------------------------------------------------
     | Filtros específicos
     ----------------------------------------------------------------- */

    /**
     * Obtener usuarios filtrados por estado.
     *
     * @param string $status Estado del usuario.
     * @param array  $columns Columnas a seleccionar.
     *
     * @return Collection<User>
     */
    public function getUsersByStatus(string $status, array $columns = ['*']): Collection;

    /**
     * Obtener usuarios filtrados por país.
     *
     * @param string $countryId ID del país.
     * @param array  $columns   Columnas a seleccionar.
     *
     * @return Collection<User>
     */
    public function getUsersByCountry(string $countryId, array $columns = ['*']): Collection;

    /**
     * Obtener usuarios referidos por un usuario específico.
     *
     * @param string $userId  ID del usuario referidor.
     * @param array  $columns Columnas a seleccionar.
     *
     * @return Collection<User>
     */
    public function getReferredUsers(string $userId, array $columns = ['*']): Collection;

    /**
     * Obtener usuarios por rol.
     *
     * @param string $roleName Nombre del rol.
     * @param array  $columns  Columnas a seleccionar.
     *
     * @return Collection<User>
     */
    public function getUsersByRole(string $roleName, array $columns = ['*']): Collection;


    /* -----------------------------------------------------------------
     | Verificación de existencia
     ----------------------------------------------------------------- */

    /**
     * Verificar si existe un usuario con un email dado.
     *
     * @param string $email Email a verificar.
     *
     * @return bool
     */
    public function existsByEmail(string $email): bool;

    /**
     * Verificar si existe un usuario con un username dado.
     *
     * @param string $username Username a verificar.
     *
     * @return bool
     */
    public function existsByUsername(string $username): bool;

    /**
     * Verificar si existe un usuario con un código de referido.
     *
     * @param string $referralCode Código de referido.
     *
     * @return bool
     */
    public function existsByReferralCode(string $referralCode): bool;


    /* -----------------------------------------------------------------
     | Utilidades
     ----------------------------------------------------------------- */

    /**
     * Actualizar el campo last_login_at y last_login_ip del usuario.
     *
     * @param string $userId ID del usuario.
     * @param string $ip     Dirección IP desde donde inició sesión.
     *
     * @return bool
     */
    public function updateLastLogin(string $userId, string $ip): bool;

    /**
     * Obtener estadísticas del usuario (personalizadas por el sistema).
     *
     * @param string $userId ID del usuario.
     *
     * @return array
     */
    public function getUserStats(string $userId): array;

    /**
     * Buscar usuarios aplicando múltiples criterios.
     *
     * Ejemplo de $criteria:
     * [
     *     'role'     => 'player',
     *     'status'   => 'ACTIVE',
     *     'country'  => 'BR',
     *     'search'   => 'juan'
     * ]
     *
     * @param array $criteria Criterios dinámicos de búsqueda.
     *
     * @return Collection<User>
     */
    public function searchUsers(array $criteria): Collection;

    /**
     * Obtener usuarios con actividad reciente.
     *
     * @param int $days  Cantidad de días hacia atrás.
     * @param int $limit Límite de resultados.
     *
     * @return Collection<User>
     */
    public function getRecentActiveUsers(int $days = 30, int $limit = 50): Collection;
}
