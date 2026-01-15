<?php

namespace App\Repositories\Users\Roles\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Users\Role;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de roles de usuario.
 *
 * Define los métodos específicos para gestionar los roles del sistema,
 * incluyendo operaciones de consulta, creación, actualización y asignación.
 */
interface RoleRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtener todos los roles activos.
     *
     * @return Collection Colección de roles activos
     */
    public function getActiveRoles(): Collection;

    /**
     * Obtener todos los roles inactivos.
     *
     * @return Collection Colección de roles inactivos
     */
    public function getInactiveRoles(): Collection;

    /**
     * Buscar rol por nombre exacto.
     *
     * @param string $name Nombre exacto del rol
     * @return Role|null El rol encontrado o null
     */
    public function getByName(string $name): ?Role;

    /**
     * Buscar roles por prefijo.
     *
     * @param string $prefix Prefijo del rol
     * @return Collection Roles que tienen el prefijo especificado
     */
    public function getByPrefix(string $prefix): Collection;

    /**
     * Obtiene los prefijos de todos los roles activos asignados a un usuario.
     *
     * @param string $userId ID del usuario
     * @return array<string> Lista de prefijos de roles
     */
    public function getRolesByUserId(string $userId): array;

    /**
     * Buscar roles por descripción parcial.
     *
     * @param string $searchTerm Término de búsqueda parcial
     * @return Collection Roles que contienen el término en la descripción
     */
    public function searchByDescription(string $searchTerm): Collection;

    /**
     * Verificar si existe un rol con el nombre dado.
     *
     * @param string $name Nombre a verificar
     * @param int|null $excludeId ID a excluir de la verificación (para updates)
     * @return bool True si existe, false en caso contrario
     */
    public function existsByName(string $name, ?int $excludeId = null): bool;

    /**
     * Crear un nuevo rol.
     *
     * @param string $name Nombre del rol
     * @param string|null $prefix Prefijo del rol (opcional)
     * @param string|null $description Descripción del rol (opcional)
     * @param bool $status Estado inicial (por defecto true)
     * @return Role El rol creado
     */
    public function createRole(string $name, ?string $prefix = null, ?string $description = null, bool $status = true): Role;

    /**
     * Actualizar información de un rol.
     *
     * @param int $roleId ID del rol
     * @param array $data Datos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function updateRole(int $roleId, array $data): bool;

    /**
     * Actualizar el estado de un rol.
     *
     * @param int $roleId ID del rol
     * @param bool $status Nuevo estado
     * @return bool True si se actualizó correctamente
     */
    public function updateStatus(int $roleId, bool $status): bool;

    /**
     * Activar un rol.
     *
     * @param int $roleId ID del rol
     * @return bool True si se activó correctamente
     */
    public function activateRole(int $roleId): bool;

    /**
     * Desactivar un rol.
     *
     * @param int $roleId ID del rol
     * @return bool True si se desactivó correctamente
     */
    public function deactivateRole(int $roleId): bool;

    /**
     * Eliminar un rol.
     *
     * @param int $roleId ID del rol
     * @return bool True si se eliminó correctamente
     */
    public function deleteRole(int $roleId): bool;

    /**
     * Actualización masiva de estado para múltiples roles.
     *
     * @param array $roleIds IDs de los roles
     * @param bool $status Nuevo estado
     * @return int Número de registros actualizados
     */
    public function bulkUpdateStatus(array $roleIds, bool $status): int;

    /**
     * Obtener estadísticas de roles.
     *
     * @return array<string, mixed> Array con estadísticas
     */
    public function getRoleStatistics(): array;

    /**
     * Obtener roles ordenados por nombre.
     *
     * @param bool $activeOnly Solo roles activos (por defecto false)
     * @return Collection Roles ordenados por nombre
     */
    public function getOrderedByName(bool $activeOnly = false): Collection;

    /**
     * Obtener roles con más usuarios asignados.
     *
     * @param int $limit Límite de resultados (por defecto 10)
     * @return Collection Roles más populares
     */
    public function getMostUsedRoles(int $limit = 10): Collection;

    /**
     * Obtener roles sin usuarios asignados.
     *
     * @return Collection Roles que no tienen usuarios
     */
    public function getUnusedRoles(): Collection;

    /**
     * Verificar si un rol está siendo utilizado por algún usuario.
     *
     * @param int $roleId ID del rol
     * @return bool True si está en uso, false en caso contrario
     */
    public function isRoleInUse(int $roleId): bool;

    /**
     * Obtener conteo de usuarios por rol.
     *
     * @param int $roleId ID del rol
     * @return int Número de usuarios que tienen este rol
     */
    public function getUserCountByRole(int $roleId): int;

    /**
     * Obtener roles con sus estadísticas de uso.
     *
     * @param bool $activeOnly Solo roles activos (por defecto true)
     * @return Collection Roles con estadísticas de uso
     */
    public function getRolesWithUsageStats(bool $activeOnly = true): Collection;

    /**
     * Crear roles por lotes.
     *
     * @param array $rolesData Array de datos de roles
     * @param bool $status Estado inicial para todos (por defecto true)
     * @return Collection Roles creados
     */
    public function createBulkRoles(array $rolesData, bool $status = true): Collection;

    /**
     * Obtener roles para uso en selects/formularios.
     *
     * @param bool $activeOnly Solo roles activos (por defecto true)
     * @return array<int, string> Array asociativo [id => name]
     */
    public function getForSelect(bool $activeOnly = true): array;

    /**
     * Validar nombre de rol.
     *
     * @param string $name Nombre a validar
     * @return bool True si es válido
     */
    public function isValidName(string $name): bool;

    /**
     * Normalizar nombre de rol.
     *
     * @param string $name Nombre a normalizar
     * @return string Nombre normalizado
     */
    public function normalizeName(string $name): string;

    /**
     * Obtener sugerencias de nombres basadas en texto parcial.
     *
     * @param string $partialText Texto parcial
     * @param int $limit Límite de sugerencias (por defecto 5)
     * @return Collection Sugerencias de roles existentes
     */
    public function getNameSuggestions(string $partialText, int $limit = 5): Collection;

    /**
     * Obtener roles por tipo de usuario (basado en prefijo).
     *
     * @param string $userType Tipo de usuario
     * @return Collection Roles aplicables al tipo de usuario
     */
    public function getRolesByUserType(string $userType): Collection;

    /**
     * Verificar si un rol puede ser eliminado.
     *
     * @param int $roleId ID del rol
     * @return bool True si puede ser eliminado
     */
    public function canBeDeleted(int $roleId): bool;

    /**
     * Obtener roles con permisos similares (basado en prefijo).
     *
     * @param string $prefix Prefijo de referencia
     * @param int $excludeId ID a excluir (opcional)
     * @return Collection Roles con prefijo similar
     */
    public function getSimilarRoles(string $prefix, ?int $excludeId = null): Collection;
}
