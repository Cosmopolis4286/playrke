<?php

namespace App\Services\Roles\Admin;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

final class RoleService
{
    public function __construct(
        private readonly RoleHierarchyService $hierarchyService,
    ) {
    }

    /**
     * Roles ordenados según la Jerarquía de Roles (mismo árbol que
     * RoleHierarchyService::buildTree() usa en /admin/permissions), no por
     * nombre alfabético — para que RoleManagerModal.vue (vista rápida en
     * /admin/users y en el botón de acceso rápido de /permissions) muestre
     * superadmin → admin → comercio → agencia → taquilla → ... en vez de un
     * orden arbitrario. Cada rol lleva además `status_changed_at`: la fecha
     * del último Audit donde su `status` cambió (null si nunca cambió desde
     * su creación) — calculada por RoleHierarchyService para no duplicar la
     * consulta de auditoría entre ambas pantallas. `icon`/`color` ya son
     * columnas nativas del modelo; `users_count`/`permissions_count` se
     * agregan con withCount (mismos contadores que ve RoleHierarchyTree.vue)
     * para que el modal muestre exactamente los mismos badges/contadores que
     * el organigrama, sin que ambas vistas se desincronicen visualmente.
     */
    public function getAll() : Collection
    {
        $orderedIds = $this->flattenTreeIds($this->hierarchyService->buildTree());
        $statusChanges = $this->hierarchyService->lastStatusChangeByRoleId();

        return Role::withCount(['users', 'permissions'])
            ->whereIn('id', $orderedIds)
            ->get()
            ->keyBy('id')
            ->sortBy(fn (Role $role) => array_search($role->id, $orderedIds, true))
            ->values()
            ->each(function(Role $role) use ($statusChanges) {
                $role->setAttribute('status_changed_at', $statusChanges[(string) $role->id] ?? null);
            });
    }

    /**
     * Excluye siempre el rol 'superadmin', y el rol 'admin' si el actor no es
     * superadmin — reasegura a nivel de service lo que RolePolicy::update()
     * ya bloquea, para que ningún otro caller que invoque el service
     * directamente (ej. un comando artisan futuro) pueda desactivar
     * accidentalmente la cuenta raíz de autorización o el rol 'admin'.
     */
    public function toggleStatus(string $roleId) : Role
    {
        $role = Role::findOrFail($roleId);

        if ($role->name === 'superadmin') {
            throw new RuntimeException('El rol superadmin no puede desactivarse.');
        }

        if ($role->name === 'admin' && ! auth()->user()?->hasRole('superadmin')) {
            throw new RuntimeException('Solo un superadmin puede modificar el rol admin.');
        }

        $role->update(['status' => ! $role->status]);

        return $role;
    }

    /**
     * Actualiza solo metadatos editables (description, sort_order, icon,
     * color). El campo `name` es la clave técnica que consumen Spatie, el
     * middleware `role:` y SidebarMenuService::exclude_roles — no se expone
     * a edición aquí para no romper autorización en cascada por un typo.
     *
     * $attributes ya viene validado/filtrado por RoleController::update()
     * (solo claves permitidas: description, sort_order, icon, color).
     */
    public function updateMeta(string $roleId, array $attributes) : Role
    {
        $role = Role::findOrFail($roleId);
        $role->update($attributes);

        return $role;
    }

    /** Aplana el árbol de jerarquía (DFS) a una lista plana de IDs, en el mismo orden visual del organigrama. */
    private function flattenTreeIds(array $nodes) : array
    {
        $ids = [];

        foreach ($nodes as $node) {
            $ids[] = $node['id'];
            if (! empty($node['children'])) {
                $ids = [...$ids, ...$this->flattenTreeIds($node['children'])];
            }
        }

        return $ids;
    }
}
