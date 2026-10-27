<?php

namespace App\Policies\Roles;

use App\Models\User;
use App\Services\Auth\RoleAuthorizationService;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy base para entidades administradas por roles.
 *
 * Esta clase centraliza reglas comunes de autorización para recursos
 * que dependen principalmente del rol del usuario y del estado del modelo.
 *
 * Provee implementaciones estándar para acciones CRUD:
 * - viewAny
 * - view
 * - create
 * - update
 * - delete
 * - restore
 * - forceDelete
 *
 * Las policies hijas solo deben implementar:
 * - requiredRole()
 *
 * Opcionalmente pueden sobrescribir:
 * - forbiddenStatuses()
 *
 * Ejemplo de uso:
 * class CommercePolicy extends BaseRolePolicy
 * {
 *     protected function requiredRole(): string|array
 *     {
 *         return ['admin', 'commerce'];
 *     }
 * }
 */
abstract class BaseRolePolicy
{
    /**
     * Autoriza la creación de un nuevo recurso.
     */
    public function create(User $user) : bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Autoriza la eliminación lógica (soft delete) del recurso.
     *
     * Por convención, se reutiliza la misma regla de update.
     */
    public function delete(User $user, Model $model) : bool
    {
        return $this->update($user, $model);
    }

    /**
     * Autoriza la visualización de una lista filtrada de recursos.
     */
    public function fetchList(User $user) : bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Autoriza la eliminación definitiva (hard delete).
     */
    public function forceDelete(User $user, Model $model) : bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Autoriza la restauración de un recurso eliminado.
     */
    public function restore(User $user, Model $model) : bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Autoriza la actualización de un recurso existente.
     *
     * Solo se permite si:
     * - El usuario tiene acceso por rol
     * - El modelo está en un estado mutable
     */
    public function update(User $user, Model $model) : bool
    {
        return $this->hasAccess($user) && $this->canMutate($model);
    }

    /**
     * Autoriza la visualización de una entidad específica.
     */
    public function view(User $user, Model $model) : bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Autoriza la visualización del listado de recursos.
     *
     * Equivalente a "viewAny" en policies Laravel estándar.
     */
    public function viewAny(User $user) : bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Determina si un modelo puede ser modificado según su estado actual.
     *
     * Si el modelo no posee el atributo `status`, se asume que es mutable.
     *
     * Esto evita errores en entidades que no manejan estados.
     */
    protected function canMutate(Model $model) : bool
    {
        // Si el modelo no tiene el atributo status, no se aplican restricciones
        if (! array_key_exists('status', $model->getAttributes())) {
            return true;
        }

        // Bloquear mutación si el status está en la lista de prohibidos
        return ! in_array(
            $model->status,
            $this->forbiddenStatuses(),
            true,
        );
    }

    /**
     * Estados del modelo que bloquean operaciones mutables (update/delete/etc).
     *
     * Las policies hijas pueden sobrescribir este método si necesitan
     * agregar más estados restringidos.
     *
     * Por defecto, se bloquea la mutación de registros eliminados.
     *
     * @return string[]
     */
    protected function forbiddenStatuses() : array
    {
        return ['DELETED'];
    }

    /**
     * Valida si el usuario tiene acceso base según el rol requerido.
     *
     * Delega en RoleAuthorizationService, la misma fuente de verdad que
     * usan middleware y gates, para que todas las capas de autorización
     * evalúen exactamente la misma regla.
     */
    protected function hasAccess(User $user) : bool
    {
        return app(RoleAuthorizationService::class)->hasAnyRole($user, $this->requiredRole());
    }

    /**
     * Rol o roles requeridos para operar sobre el recurso.
     *
     * Puede retornar:
     * - Un string: 'admin'
     * - Un array: ['admin', 'treasurer']
     *
     * Esto permite reutilizar la lógica de autorización en policies hijas
     * sin duplicar validaciones.
     *
     * @return string|string[]
     */
    abstract protected function requiredRole() : string|array;
}
