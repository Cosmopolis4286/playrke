<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy de autorización sobre el modelo User en operaciones administrativas
 * transversales (no ligadas a un dominio de rol específico como Commerce/Seller).
 *
 * Capa de defensa independiente del middleware `role:superadmin` de ruta
 * (`role_protected_routes()` en routes/modules/admin.php) — mismo patrón que
 * PermissionPolicy: si el middleware de la ruta cambia o el controlador se
 * reutiliza en otro contexto, esta Policy sigue exigiendo superadmin por su cuenta.
 */
class UserPolicy extends BaseRolePolicy
{
    /**
     * Crear un nuevo administrador (rol 'admin') — pantalla /admins. Solo
     * superadmin: un admin no puede promoverse a sí mismo ni a otros a su
     * propio nivel, mismo criterio que
     * AdminUserService::guardRoleChange() aplica al cambiar el rol de un
     * usuario existente a 'admin'.
     */
    public function createAdministrator(User $actingUser) : bool
    {
        return $this->hasAccess($actingUser);
    }

    /**
     * Editar los datos básicos (nombre, email, teléfono, país, username) de
     * un administrador existente — pantalla /admins. A diferencia de
     * manageAdministrator(), SÍ permite auto-edición: un superadmin puede
     * actualizar su propia ficha (el status, en cambio, nunca se toca por
     * esta vía — ver AdministratorUpdateDTO, que no incluye ese campo).
     *
     * @param Model $targetUser tipado como Model para mantener la firma
     *                          compatible con BaseRolePolicy::update()
     */
    public function editAdministrator(User $actingUser, Model $targetUser) : bool
    {
        if (! $this->hasAccess($actingUser)) {
            return false;
        }

        return $targetUser instanceof User
            && ($targetUser->hasRole('admin') || $targetUser->hasRole('superadmin'));
    }

    /**
     * Gestionar acciones sensibles (activar/desactivar, resetear contraseña,
     * eliminar) de un administrador existente — pantalla /admins.
     *
     * Reglas, en orden:
     * - Solo superadmin tiene acceso base a esta pantalla.
     * - Nadie puede aplicarse estas acciones a sí mismo (evita que un admin
     *   se desactive/elimine/resetee su propia contraseña y quede el sistema
     *   sin decisión clara de quién lo reactiva).
     * - El objetivo debe ser realmente un admin o superadmin — evita que este
     *   endpoint se use como atajo para mutar cualquier User del sistema.
     *
     * @param Model $targetUser tipado como Model para mantener la firma
     *                          compatible con BaseRolePolicy::update()
     */
    public function manageAdministrator(User $actingUser, Model $targetUser) : bool
    {
        if (! $this->hasAccess($actingUser)) {
            return false;
        }

        if ($actingUser->id === $targetUser->id) {
            return false;
        }

        return $targetUser instanceof User
            && ($targetUser->hasRole('admin') || $targetUser->hasRole('superadmin'));
    }

    /**
     * Ver/editar los permisos específicos (specific_permissions) de otro
     * usuario. Aplica a cualquier usuario del sistema, incluidos otros
     * admin — EXCEPTO superadmin: esa cuenta ya tiene el permiso
     * 'superadmin.access' (acceso total, ver PermissionSeeder), así que
     * otorgarle/quitarle permisos individuales no tiene ningún efecto real
     * y solo genera confusión sobre qué controla su acceso.
     *
     * @param Model $targetUser tipado como Model (no User) para mantener la
     *                          firma compatible con el resto de métodos de BaseRolePolicy
     */
    public function managePermissions(User $actingUser, Model $targetUser) : bool
    {
        if (! $this->hasAccess($actingUser)) {
            return false;
        }

        return ! ($targetUser instanceof User && $targetUser->hasRole('superadmin'));
    }

    protected function requiredRole() : string|array
    {
        return 'superadmin';
    }
}
