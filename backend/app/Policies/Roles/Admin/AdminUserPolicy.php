<?php

namespace App\Policies\Roles\Admin;

use App\Models\User;

/**
 * Policy de autorización para el panel admin de Usuarios (/admin/user/*,
 * AdminUserController). Capa de defensa independiente del middleware
 * `multiPermission:users.view,users.edit,users.delete` de ruta — si el
 * middleware de la ruta cambia, esta Policy sigue exigiendo el permiso
 * correcto por su cuenta (mismo espíritu que KycPolicy/JobsCenterPolicy).
 *
 * Deliberadamente NO es App\Policies\UserPolicy: esa clase sirve al flujo
 * /admins (gestión de administradores, requiredRole()='superadmin' puro,
 * ver managePermissions/createAdministrator/editAdministrator) — un flujo
 * distinto con reglas distintas. Usar UserPolicy::requiredRole() aquí
 * rompería el acceso hoy-funcional de 'admin' (no superadmin) a
 * /admin/user/*, que hoy solo depende de multiPermission:users.* de ruta.
 */
class AdminUserPolicy
{
    public function delete(User $user) : bool
    {
        return $user->hasPermissionTo('users.delete');
    }

    public function edit(User $user) : bool
    {
        return $user->hasPermissionTo('users.edit');
    }

    public function view(User $user) : bool
    {
        return $this->viewAny($user);
    }

    public function viewAny(User $user) : bool
    {
        return $user->hasAnyPermission(['users.view', 'users.edit', 'users.delete']);
    }
}
