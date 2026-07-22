<?php

namespace App\Policies\Roles\Admin;

use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy de autorización para la gestión de Roles (CRUD de metadatos +
 * activar/desactivar). Solo superadmin — mismo patrón que PermissionPolicy.
 *
 * El rol 'superadmin' en sí nunca puede desactivarse ni editarse: es la
 * cuenta raíz de autorización (ver RoleAuthorizationService::isSuperadmin);
 * desactivarlo dejaría al propio superadmin sin acceso administrativo.
 */
class RolePolicy extends BaseRolePolicy
{
    protected function requiredRole(): string|array
    {
        return 'superadmin';
    }

    /**
     * @param Model $model Tipado como Model (no Role) para mantener la firma
     *   compatible con BaseRolePolicy::update() — PHP no permite estrechar
     *   el tipo de un parámetro en un override.
     */
    public function update(User $user, Model $model): bool
    {
        return $this->hasAccess($user) && $model->name !== 'superadmin';
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->update($user, $model);
    }
}
