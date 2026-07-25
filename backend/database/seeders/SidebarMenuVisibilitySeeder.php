<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\SidebarMenuVisibility;
use App\Services\Menu\SidebarMenuService;
use Illuminate\Database\Seeder;

/**
 * Siembra el estado ACTUAL de `exclude_roles` (SidebarMenuService::$menuStructure)
 * como filas is_visible=false — reproduce exactamente el comportamiento vigente
 * mientras $menuStructure deja de usar exclude_roles como mecanismo de decisión.
 * No siembra filas para combinaciones sin exclude_roles (semántica "sin fila
 * = visible", igual que ModuleSeeder).
 */
class SidebarMenuVisibilitySeeder extends Seeder
{
    public function run(): void
    {
        $menuStructure = app(SidebarMenuService::class)->menuStructure;

        // name (normalizado a minúsculas por Role::setNameAttribute) => id.
        // exclude_roles compara contra $user->getRoleNames(), que devuelve
        // Role::name — NO Role::prefix (que en BD está en mayúsculas, ej.
        // 'SUPERADMIN') — por eso se resuelve por name, no por prefix.
        $roleIdsByName = Role::pluck('id', 'name')->all();

        $rows = 0;

        foreach ($menuStructure as $parentKey => $parentItem) {
            $rows += $this->seedItem($parentKey, $parentItem, $roleIdsByName);

            foreach ($parentItem['submenu'] ?? [] as $subKey => $subItem) {
                $itemKey = "{$parentKey}.{$subKey}";
                $rows += $this->seedItem($itemKey, $subItem, $roleIdsByName);
            }
        }

        $this->command->info("✔ SidebarMenuVisibilitySeeder: {$rows} filas de exclusión procesadas.");
    }

    /**
     * @param array<string, string> $roleIdsByName
     */
    private function seedItem(string $itemKey, array $item, array $roleIdsByName): int
    {
        $excludeRoles = $item['exclude_roles'] ?? [];
        $count = 0;

        foreach ($excludeRoles as $roleName) {
            $roleId = $roleIdsByName[$roleName] ?? null;

            if ($roleId === null) {
                $this->command->warn("  ⚠ Rol '{$roleName}' no encontrado (item: {$itemKey})");
                continue;
            }

            SidebarMenuVisibility::updateOrCreate(
                ['item_key' => $itemKey, 'role_id' => $roleId],
                ['is_visible' => false]
            );
            $count++;
        }

        return $count;
    }
}
