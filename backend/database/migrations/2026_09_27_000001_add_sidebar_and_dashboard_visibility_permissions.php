<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Migración de datos (no seeder): siembra sidebar-menu.view/manage y
 * dashboard-widgets.view/manage, y se los asigna a 'superadmin' (ya los
 * tiene todo permiso vía RolePermissionSeeder en instalaciones frescas,
 * pero producción no vuelve a correr seeders — ver deploy.sh, que solo
 * ejecuta `migrate --force`). Sin esta migración, producción se quedaría
 * sin los permisos nuevos y SidebarMenuVisibilityPolicy/
 * DashboardWidgetVisibilityPolicy (permission-based tras esta migración)
 * bloquearían a superadmin mismo hasta el próximo `db:seed` manual.
 *
 * Deliberadamente NO se asigna a 'admin': el árbol de permisos de admin
 * vive en RolePermissionSeeder::run() como lista explícita — agregarlo
 * aquí duplicaría la fuente de verdad y el criterio del plan aprobado es
 * que sidebar-menu/dashboard-widgets siguen siendo exclusivos de
 * superadmin (mismo nivel de acceso que permissions/modules/roles-admin,
 * que se quedan en role:superadmin puro por la misma razón de seguridad).
 *
 * Idempotente: firstOrCreate() en los permisos, y givePermissionTo() en la
 * asignación — Spatie no duplica la fila pivote si el rol ya tiene el
 * permiso, y a diferencia de syncPermissions() (que reemplaza TODO el set)
 * no toca ningún otro permiso que superadmin ya tenga.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['name' => 'sidebar-menu.view', 'guard_name' => 'web', 'description' => 'Ver la matriz de visibilidad del sidebar por rol'],
            ['name' => 'sidebar-menu.manage', 'guard_name' => 'web', 'description' => 'Editar qué ve cada rol en el sidebar'],
            ['name' => 'dashboard-widgets.view', 'guard_name' => 'web', 'description' => 'Ver la matriz de visibilidad de widgets del dashboard por rol'],
            ['name' => 'dashboard-widgets.manage', 'guard_name' => 'web', 'description' => 'Editar qué widgets del dashboard ve cada rol'],
        ];

        $names = [];
        foreach ($permissions as $attributes) {
            $names[] = $attributes['name'];
            Permission::firstOrCreate(
                ['name' => $attributes['name'], 'guard_name' => $attributes['guard_name']],
                $attributes,
            );
        }

        $superadmin = Role::where('name', 'superadmin')->where('guard_name', 'web')->first();
        if ($superadmin) {
            $superadmin->givePermissionTo(Permission::whereIn('name', $names)->get());
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'sidebar-menu.view',
            'sidebar-menu.manage',
            'dashboard-widgets.view',
            'dashboard-widgets.manage',
        ])->delete();
    }
};
