<?php

namespace App\Policies\Content;

use App\Models\Content\LandingSection;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * Contenido de marketing sin superficie de riesgo de dinero/KYC — mismo
 * nivel de acceso que Prizes (admin,superadmin), más bajo que los módulos
 * de configuración de sistema (permisos, admins) que exigen superadmin.
 */
class LandingSectionPolicy extends BaseRolePolicy
{
    // El catálogo de 8 section_key es fijo por diseño (is_removable) — no se
    // exponen rutas de create/destroy, pero se niega explícito por si algo
    // llegara a invocar estas acciones vía Gate directamente.
    public function create(User $user) : bool
    {
        return false;
    }

    public function delete(User $user, Model $model) : bool
    {
        return false;
    }

    public function forceDelete(User $user, Model $model) : bool
    {
        return false;
    }

    public function reorder(User $user) : bool
    {
        return $this->hasAccess($user);
    }

    public function toggleStatus(User $user, LandingSection $section) : bool
    {
        return $this->hasAccess($user);
    }

    protected function requiredRole() : string|array
    {
        return ['superadmin', 'admin'];
    }
}
