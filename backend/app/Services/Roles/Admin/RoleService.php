<?php

namespace App\Services\Roles\Admin;

use App\Models\Audit;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

final class RoleService
{
    public function __construct(
        private readonly RoleHierarchyService $hierarchyService,
    ) {}

    /**
     * Roles ordenados según la Jerarquía de Roles (mismo árbol que
     * RoleHierarchyService::buildTree() usa en /admin/permissions), no por
     * nombre alfabético — para que RoleManagerModal.vue (ambas pantallas:
     * /permissions y /admin/users) muestre superadmin → admin → comercio →
     * agencia → taquilla → ... en vez de un orden arbitrario. Cada rol lleva
     * además `status_changed_at`: la fecha del último Audit donde su
     * `status` cambió (null si nunca cambió desde su creación).
     */
    public function getAll(): Collection
    {
        $orderedIds = $this->flattenTreeIds($this->hierarchyService->buildTree());
        $statusChanges = $this->lastStatusChangeByRoleId();

        return Role::whereIn('id', $orderedIds)
            ->get()
            ->keyBy('id')
            ->sortBy(fn (Role $role) => array_search($role->id, $orderedIds, true))
            ->values()
            ->each(function (Role $role) use ($statusChanges) {
                $role->setAttribute('status_changed_at', $statusChanges[(string) $role->id] ?? null);
            });
    }

    /** Aplana el árbol de jerarquía (DFS) a una lista plana de IDs, en el mismo orden visual del organigrama. */
    private function flattenTreeIds(array $nodes): array
    {
        $ids = [];

        foreach ($nodes as $node) {
            $ids[] = $node['id'];
            if (!empty($node['children'])) {
                $ids = [...$ids, ...$this->flattenTreeIds($node['children'])];
            }
        }

        return $ids;
    }

    /**
     * Último cambio de `status` por rol, leído de la auditoría (Role usa el
     * trait Auditable). new_values guarda TODOS los atributos en cada
     * 'updated' (no solo el campo modificado), así que se filtra en PHP
     * comparando old_values.status vs new_values.status — el volumen de
     * roles/audits es bajo (decenas), no amerita una consulta JSON compleja.
     *
     * @return array<string, ?Carbon> role_id (string) => fecha del cambio
     */
    private function lastStatusChangeByRoleId(): array
    {
        return Audit::ofModel(Role::class)
            ->ofEvent('updated')
            ->latest('created_at')
            ->get(['auditable_id', 'old_values', 'new_values', 'created_at'])
            ->map(function (Audit $audit) {
                $audit->old_values = $this->decodeAuditValues($audit->old_values);
                $audit->new_values = $this->decodeAuditValues($audit->new_values);
                return $audit;
            })
            ->filter(fn (Audit $audit) =>
                array_key_exists('status', $audit->old_values ?? [])
                && array_key_exists('status', $audit->new_values ?? [])
                && $audit->old_values['status'] !== $audit->new_values['status']
            )
            ->groupBy(fn (Audit $audit) => (string) (is_array($audit->auditable_id) ? ($audit->auditable_id[0] ?? '') : $audit->auditable_id))
            ->map(fn (SupportCollection $audits) => $audits->first()->created_at)
            ->all();
    }

    /**
     * Decodifica old_values/new_values tolerando audits antiguos guardados
     * con doble serialización (Auditable::serializeValue() hacía json_encode()
     * manual sobre columnas que ya tienen cast 'array', ver fix en ese trait)
     * — esos registros históricos quedaron con un string JSON dentro de la
     * columna JSON en vez de un objeto, así que el cast del modelo entrega
     * ese string tal cual y hay que decodificarlo una vez más aquí.
     */
    private function decodeAuditValues(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
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
    public function updateMeta(string $roleId, array $attributes): Role
    {
        $role = Role::findOrFail($roleId);
        $role->update($attributes);

        return $role;
    }

    /**
     * Excluye siempre el rol 'superadmin' — reasegura a nivel de service lo
     * que RolePolicy::update() ya bloquea, para que ningún otro caller que
     * invoque el service directamente (ej. un comando artisan futuro) pueda
     * desactivar accidentalmente la cuenta raíz de autorización.
     */
    public function toggleStatus(string $roleId): Role
    {
        $role = Role::findOrFail($roleId);

        if ($role->name === 'superadmin') {
            throw new \RuntimeException('El rol superadmin no puede desactivarse.');
        }

        $role->update(['status' => ! $role->status]);

        return $role;
    }
}
