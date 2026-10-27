<?php

namespace App\Policies\Roles\Admin;

use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy de autorización para la gestión de Roles (CRUD de metadatos +
 * activar/desactivar). Superadmin y admin — mismo patrón de "admin con
 * techo" que AdminUserService::guardMutation() usa para la gestión de
 * usuarios: un admin puede gestionar roles, pero no el rol 'superadmin' ni
 * el rol 'admin' (ni siquiera el suyo propio) — evita que un admin se
 * desactive a sí mismo o a otro admin y quede con control exclusivo del
 * panel, o que desactive la cuenta raíz de autorización.
 *
 * El rol 'superadmin' en sí nunca puede desactivarse ni editarse, ni
 * siquiera por otro superadmin: es la cuenta raíz de autorización (ver
 * RoleAuthorizationService::isSuperadmin); desactivarlo dejaría al propio
 * superadmin sin acceso administrativo.
 */
class RolePolicy extends BaseRolePolicy
{
    public function delete(User $user, Model $model) : bool
    {
        return $this->update($user, $model);
    }

    /**
     * @param Model $model tipado como Model (no Role) para mantener la firma
     *                     compatible con BaseRolePolicy::update() — PHP no permite estrechar
     *                     el tipo de un parámetro en un override
     */
    public function update(User $user, Model $model) : bool
    {
        if (! $this->hasAccess($user) || $model->name === 'superadmin') {
            return false;
        }

        // Un admin (no superadmin) no puede gestionar el rol 'admin' —
        // mismo criterio que AdminUserService::guardMutation() aplica a
        // usuarios individuales, aquí aplicado al ROL en sí.
        return ! ($model->name === 'admin' && ! $user->hasRole('superadmin'));
    }

    protected function requiredRole() : string|array
    {
        return ['superadmin', 'admin'];
    }
}
