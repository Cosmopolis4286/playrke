<?php

namespace App\Services\Dashboard;

use App\Models\User;

/**
 * Filtro de visibilidad de widgets del dashboard para UN usuario, con la
 * matriz de excepciones ya precargada en memoria — creado vía
 * DashboardWidgetVisibilityService::filterFor($user) al inicio de
 * dashboardContext(), reutilizado para cada stat/entity/quicklink sin
 * volver a golpear la BD por cada uno.
 *
 * Dos condiciones, ambas obligatorias (ver DashboardWidgetVisibilityService
 * para el razonamiento completo):
 *   1. Permiso Spatie real del usuario (si el widget tiene uno).
 *   2. Visibilidad configurada para alguno de los roles del usuario.
 */
final class DashboardWidgetFilter
{
    /** @var string[] */
    private readonly array $userRoleIds;

    /**
     * @param array<string, array<string, bool>> $visibilityMatrix item_key => [role_id => bool]
     */
    public function __construct(
        private readonly User $user,
        private readonly array $visibilityMatrix,
    ) {
        $this->userRoleIds = $user->roles->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    public function isVisible(string $itemKey, ?string $permission): bool
    {
        if ($permission !== null && !$this->user->hasPermissionTo($permission)) {
            return false;
        }

        if (empty($this->userRoleIds)) {
            return true;
        }

        $roleVisibility = $this->visibilityMatrix[$itemKey] ?? [];

        foreach ($this->userRoleIds as $roleId) {
            if ($roleVisibility[$roleId] ?? true) {
                return true;
            }
        }

        return false;
    }
}
