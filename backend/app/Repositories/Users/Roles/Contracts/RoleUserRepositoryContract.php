<?php

namespace App\Repositories\Users\Roles\Contracts;

use App\Models\Users\User;
use App\Repositories\Base\Contracts\BaseRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de relaciones usuario-rol.
 *
 * Define las operaciones específicas para gestionar las asignaciones
 * de roles a usuarios, incluyendo consultas por usuario, rol, estado
 * de activación, fechas de expiración y gestión de asignaciones.
 */
interface RoleUserRepositoryContract extends BaseRepositoryContract
{
    /**
     * Obtiene todas las asignaciones de roles de un usuario específico.
     *
     * @param string $userId ID del usuario
     * @return Collection<RoleUser> Colección de asignaciones del usuario
     */
    public function getByUser(string $userId): Collection;

    /**
     * Obtiene todos los usuarios que tienen un rol específico.
     *
     * @param string $roleId ID del rol
     * @return Collection<RoleUser> Colección de usuarios con el rol
     */
    public function getByRole(string $roleId): Collection;

    /**
     * Obtiene las asignaciones activas de un usuario.
     *
     * @param string $userId ID del usuario
     * @return Collection<RoleUser> Asignaciones activas del usuario
     */
    public function getActiveByUser(string $userId): Collection;

    /**
     * Obtiene las asignaciones expiradas de un usuario.
     *
     * @param string $userId ID del usuario
     * @return Collection<RoleUser> Asignaciones expiradas del usuario
     */
    public function getExpiredByUser(string $userId): Collection;

    /**
     * Obtiene todas las asignaciones activas del sistema.
     *
     * @return Collection<RoleUser> Todas las asignaciones activas
     */
    public function getAllActive(): Collection;

    /**
     * Obtiene todas las asignaciones expiradas del sistema.
     *
     * @return Collection<RoleUser> Todas las asignaciones expiradas
     */
    public function getAllExpired(): Collection;

    /**
     * Verifica si un usuario tiene un rol específico activo.
     *
     * @param string $userId ID del usuario
     * @param string $roleId ID del rol
     * @return bool True si el usuario tiene el rol activo
     */
    public function hasActiveRole(string $userId, string $roleId): bool;

    /**
     * Verifica si un usuario posee al menos uno de los roles indicados
     *
     * @param User $user
     * @param array $roles Prefijos de roles ['admin', 'commerce', ...]
     * @return bool
     */
    public function userHasAnyRole(User $user, array $roles): bool;

    /**
     * Verifica si el usuario tiene un rol específico
     *
     * @param User $user
     * @param string $role
     * @return bool
     */
    public function userHasRole(User $user, string $role): bool;

    /**
     * Obtiene la asignación específica de un usuario y rol.
     *
     * @param string $userId ID del usuario
     * @param string $roleId ID del rol
     * @return mixed Asignación encontrada o null
     */
    public function getUserRole(string $userId, string $roleId): mixed;

    /**
     * Asigna un rol a un usuario.
     *
     * @param string $userId ID del usuario
     * @param string $roleId ID del rol
     * @param string|null $assignedBy ID del usuario que asigna
     * @param string|null $expiresAt Fecha de expiración (opcional)
     * @return mixed Asignación creada
     */
    public function assignRole(string $userId, string $roleId, ?string $assignedBy = null, ?string $expiresAt = null): mixed;

    /**
     * Remueve un rol de un usuario.
     *
     * @param string $userId ID del usuario
     * @param string $roleId ID del rol
     * @return bool True si se removió correctamente
     */
    public function removeRole(string $userId, string $roleId): bool;

    /**
     * Actualiza la fecha de expiración de una asignación.
     *
     * @param string $userId ID del usuario
     * @param string $roleId ID del rol
     * @param string|null $expiresAt Nueva fecha de expiración
     * @return bool True si se actualizó correctamente
     */
    public function updateExpiration(string $userId, string $roleId, ?string $expiresAt): bool;

    /**
     * Extiende la expiración de una asignación por días.
     *
     * @param string $userId ID del usuario
     * @param string $roleId ID del rol
     * @param int $days Días a extender
     * @return bool True si se extendió correctamente
     */
    public function extendExpiration(string $userId, string $roleId, int $days): bool;

    /**
     * Marca una asignación como permanente (sin expiración).
     *
     * @param string $userId ID del usuario
     * @param string $roleId ID del rol
     * @return bool True si se marcó como permanente
     */
    public function makePermanent(string $userId, string $roleId): bool;

    /**
     * Obtiene asignaciones que expiran en un rango de fechas.
     *
     * @param string $startDate Fecha inicio
     * @param string $endDate Fecha fin
     * @return Collection<RoleUser> Asignaciones que expiran en el rango
     */
    public function getExpiringInRange(string $startDate, string $endDate): Collection;

    /**
     * Obtiene asignaciones que expiran en los próximos días.
     *
     * @param int $days Número de días
     * @return Collection<RoleUser> Asignaciones que expiran pronto
     */
    public function getExpiringSoon(int $days = 7): Collection;

    /**
     * Obtiene asignaciones realizadas por un usuario específico.
     *
     * @param string $assignedBy ID del usuario que asignó
     * @return Collection<RoleUser> Asignaciones realizadas por el usuario
     */
    public function getAssignedBy(string $assignedBy): Collection;

    /**
     * Remueve todos los roles de un usuario.
     *
     * @param string $userId ID del usuario
     * @return int Número de roles removidos
     */
    public function removeAllUserRoles(string $userId): int;

    /**
     * Remueve un rol de todos los usuarios.
     *
     * @param string $roleId ID del rol
     * @return int Número de asignaciones removidas
     */
    public function removeRoleFromAllUsers(string $roleId): int;

    /**
     * Asigna múltiples roles a un usuario.
     *
     * @param string $userId ID del usuario
     * @param array $roleIds Array de IDs de roles
     * @param string|null $assignedBy ID del usuario que asigna
     * @param string|null $expiresAt Fecha de expiración (opcional)
     * @return Collection<RoleUser> Asignaciones creadas
     */
    public function assignMultipleRoles(string $userId, array $roleIds, ?string $assignedBy = null, ?string $expiresAt = null): Collection;

    /**
     * Asigna un rol a múltiples usuarios.
     *
     * @param array $userIds Array de IDs de usuarios
     * @param string $roleId ID del rol
     * @param string|null $assignedBy ID del usuario que asigna
     * @param string|null $expiresAt Fecha de expiración (opcional)
     * @return Collection<RoleUser> Asignaciones creadas
     */
    public function assignRoleToMultipleUsers(array $userIds, string $roleId, ?string $assignedBy = null, ?string $expiresAt = null): Collection;

    /**
     * Obtiene estadísticas de asignaciones de roles.
     *
     * @return array Estadísticas de asignaciones
     */
    public function getRoleAssignmentStats(): array;

    /**
     * Obtiene estadísticas de asignaciones por rol.
     *
     * @param string $roleId ID del rol
     * @return array Estadísticas del rol específico
     */
    public function getRoleStats(string $roleId): array;

    /**
     * Obtiene estadísticas de asignaciones por usuario.
     *
     * @param string $userId ID del usuario
     * @return array Estadísticas del usuario específico
     */
    public function getUserStats(string $userId): array;

    /**
     * Valida los datos de una asignación antes de crear/actualizar.
     *
     * @param array $data Datos a validar
     * @param string|null $excludeId ID a excluir de validaciones de unicidad
     * @return array Array de errores de validación
     */
    public function validateAssignmentData(array $data, ?string $excludeId = null): array;

    /**
     * Limpia asignaciones expiradas del sistema.
     *
     * @param bool $dryRun Si es true, solo cuenta sin eliminar
     * @return int Número de asignaciones limpiadas o contadas
     */
    public function cleanExpiredAssignments(bool $dryRun = false): int;

    /**
     * Obtiene roles únicos asignados a un usuario.
     *
     * @param string $userId ID del usuario
     * @return Collection<Role> Roles únicos del usuario
     */
    public function getUserUniqueRoles(string $userId): Collection;

    /**
     * Obtiene usuarios únicos que tienen un rol.
     *
     * @param string $roleId ID del rol
     * @return Collection<User> Usuarios únicos con el rol
     */
    public function getRoleUniqueUsers(string $roleId): Collection;

    /**
     * Verifica si una asignación está activa por ID.
     *
     * @param string $assignmentId ID de la asignación
     * @return bool True si está activa
     */
    public function isAssignmentActive(string $assignmentId): bool;

    /**
     * Obtiene la distribución de roles por tipo de usuario.
     *
     * @return array Distribución de roles
     */
    public function getRoleDistribution(): array;

    /**
     * Sincroniza los roles de un usuario con un array dado.
     *
     * @param string $userId ID del usuario
     * @param array $roleIds Array de IDs de roles deseados
     * @param string|null $assignedBy ID del usuario que sincroniza
     * @return array Resultado de la sincronización (añadidos, removidos)
     */
    public function syncUserRoles(string $userId, array $roleIds, ?string $assignedBy = null): array;
}
