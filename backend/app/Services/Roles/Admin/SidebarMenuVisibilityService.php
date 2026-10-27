<?php

namespace App\Services\Roles\Admin;

use App\Models\Role;
use App\Models\SidebarMenuVisibility;
use App\Services\Menu\SidebarMenuService;
use Illuminate\Support\Facades\DB;

/**
 * Gestión de visibilidad del sidebar por rol (item_key × role_id).
 *
 * Distinto de ModuleService: aquél apaga un item para TODOS los roles
 * (feature flag global); este gestiona qué rol específico ve cada item,
 * reemplazando el array `exclude_roles` hardcodeado en
 * SidebarMenuService::$menuStructure.
 */
final class SidebarMenuVisibilityService
{
    public function __construct(
        private readonly SidebarMenuService $sidebarMenuService,
    ) {
    }

    /**
     * Aplica un lote de cambios de visibilidad y/o orden. `sort_order`
     * ausente en un change no toca la columna (updateOrCreate solo escribe
     * las llaves presentes en el segundo array).
     *
     * @param array<int, array{item_key: string, role_id: string, is_visible?: bool, sort_order?: int|null}> $changes
     */
    public function applyChanges(array $changes) : void
    {
        DB::transaction(function() use ($changes) {
            foreach ($changes as $change) {
                $values = array_intersect_key($change, array_flip(['is_visible', 'sort_order']));

                SidebarMenuVisibility::updateOrCreate(
                    ['item_key' => $change['item_key'], 'role_id' => $change['role_id']],
                    $values,
                );
            }
        });
    }

    /**
     * Árbol completo de items del sidebar (estructura, solo lectura) — la
     * fuente de verdad de title/permission sigue siendo $menuStructure en PHP.
     */
    public function getMenuItems() : array
    {
        return $this->sidebarMenuService->getFlatMenuItemsForMatrix();
    }

    /**
     * Roles activos, ordenados, para las columnas de la matriz.
     *
     * @return array<int, array{id: string, name: string, prefix: string}>
     */
    public function getRoles() : array
    {
        return Role::where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'prefix'])
            ->map(fn (Role $role) => [
                'id' => (string) $role->id,
                'name' => $role->name,
                'prefix' => $role->prefix ?? $role->name,
            ])
            ->toArray();
    }

    /**
     * Orden actual: item_key => [role_id => int|null]. Ausencia de entrada,
     * o valor null, significa "orden natural de $menuStructure" (misma
     * semántica de excepción que getVisibilityMatrix()).
     *
     * @return array<string, array<string, int|null>>
     */
    public function getSortOrderMatrix() : array
    {
        return SidebarMenuVisibility::query()
            ->whereNotNull('sort_order')
            ->get(['item_key', 'role_id', 'sort_order'])
            ->groupBy('item_key')
            ->map(fn ($rows) => $rows->pluck('sort_order', 'role_id')->toArray())
            ->toArray();
    }

    /**
     * Visibilidad actual: item_key => [role_id => bool]. Solo incluye
     * excepciones explícitas — el frontend interpreta "ausencia de entrada"
     * como visible=true (misma semántica que la BD).
     *
     * @return array<string, array<string, bool>>
     */
    public function getVisibilityMatrix() : array
    {
        return SidebarMenuVisibility::query()
            ->get(['item_key', 'role_id', 'is_visible'])
            ->groupBy('item_key')
            ->map(fn ($rows) => $rows->pluck('is_visible', 'role_id')->toArray())
            ->toArray();
    }
}
