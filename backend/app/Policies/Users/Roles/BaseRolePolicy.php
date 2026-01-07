<?php

namespace App\Policies;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy base para entidades administradas por roles.
 *
 * Centraliza:
 * - Validación por rol
 * - Reglas comunes de estado
 * - Convenciones CRUD estándar
 *
 * Diseñada para ser extendida por Policies de dominio
 * (CommercePolicy, CustomerPolicy, TreasurerPolicy, etc.).
 */
abstract class BaseRolePolicy
{
    /**
     * Rol requerido para operar el recurso.
     *
     * Ej: 'admin', 'treasurer'
     */
    abstract protected function requiredRole(): string;

    /**
     * Estados no operables por defecto.
     *
     * Las Policies hijas pueden sobrescribir este método
     * para ampliar o reducir restricciones.
     *
     * @return string[]
     */
    protected function forbiddenStatuses(): array
    {
        return ['DELETED'];
    }

    /**
     * Valida acceso base por rol.
     */
    protected function hasAccess(User $user): bool
    {
        return $user->hasRole($this->requiredRole());
    }

    /**
     * Determina si el modelo puede ser modificado
     * según su estado actual.
     */
    protected function canMutate(Model $model): bool
    {
        if (!property_exists($model, 'status')) {
            return true;
        }

        return !in_array(
            $model->status,
            $this->forbiddenStatuses(),
            true
        );
    }

    /**
     * Ver listado de recursos.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Ver una entidad específica.
     */
    public function view(User $user, Model $model): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Crear un nuevo recurso.
     */
    public function create(User $user): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Actualizar un recurso existente.
     */
    public function update(User $user, Model $model): bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($model);
    }

    /**
     * Eliminar un recurso.
     */
    public function delete(User $user, Model $model): bool
    {
        return $this->update($user, $model);
    }

    /**
     * Restaurar un recurso eliminado.
     *
     * Por defecto deshabilitado.
     */
    public function restore(User $user, Model $model): bool
    {
        return false;
    }

    /**
     * Eliminación definitiva (hard delete).
     *
     * Por defecto deshabilitado.
     */
    public function forceDelete(User $user, Model $model): bool
    {
        return false;
    }
}
