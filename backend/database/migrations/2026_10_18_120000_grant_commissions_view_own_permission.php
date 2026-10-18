<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use App\Models\SidebarMenuVisibility;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/**
 * Migración de datos (no seeder): "Mis Comisiones". Producción no vuelve a
 * correr seeders (solo `migrate --force`), por eso además de
 * PermissionSeeder/RolePermissionSeeder/SidebarMenuVisibilitySeeder va esto:
 *  - crea commissions.view-own y lo asigna a los roles de negocio;
 *  - oculta el ítem del menú a quienes no tienen comisiones propias
 *    (superadmin tiene todos los permisos y sin esto vería un ítem con 403).
 * Idempotente.
 */
return new class extends Migration
{
    private const PERMISSION = 'commissions.view-own';
    private const ROLES = ['seller', 'subsidiary', 'commerce', 'customer'];
    private const MENU_ITEM = 'wallet.my_commissions';
    private const HIDDEN_FOR = ['admin', 'player', 'superadmin', 'treasurer'];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(
            ['name' => self::PERMISSION, 'guard_name' => 'web'],
            ['description' => 'Ver el histórico de comisiones propias ("Mis Comisiones")'],
        );

        foreach (Role::whereIn('name', self::ROLES)->where('guard_name', 'web')->get() as $role) {
            $role->givePermissionTo(self::PERMISSION);
        }

        foreach (Role::whereIn('name', self::HIDDEN_FOR)->get(['id']) as $role) {
            SidebarMenuVisibility::updateOrCreate(
                ['item_key' => self::MENU_ITEM, 'role_id' => $role->id],
                ['is_visible' => false],
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (Role::whereIn('name', self::ROLES)->where('guard_name', 'web')->get() as $role) {
            if ($role->hasPermissionTo(self::PERMISSION)) {
                $role->revokePermissionTo(self::PERMISSION);
            }
        }

        SidebarMenuVisibility::where('item_key', self::MENU_ITEM)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
