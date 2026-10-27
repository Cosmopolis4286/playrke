<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/*
 * Migración de datos (no seeder): permisos de las fichas propias "Mi Cliente"
 * (customer) y "Mi Tesorería" (treasurer) — producción no vuelve a correr
 * seeders (deploy.sh solo ejecuta `migrate --force`). Mismo criterio que
 * 2026_09_27_120000_grant_seller_bingo_report_permissions.
 *
 *  - customers.manage-own  → customer
 *  - treasurers.manage-own → treasurer
 *  - treasurers.view       → admin (vista admin vía target_user_id; superadmin
 *    tiene bypass total)
 *  - revoca sellers.view a customer: un customer ya no puede tener vendedores
 *    (ManagerSellerPolicy::requiredRole()) y ese permiso solo lo llevaba a
 *    pantallas de vendedores que ahora le dan 403.
 *
 * Idempotente: firstOrCreate + givePermissionTo (no duplica la fila pivote).
 */
return new class extends Migration
{
    private const GRANTS = [
        'customer' => ['customers.manage-own'],
        'treasurer' => ['treasurers.manage-own'],
        'admin' => ['treasurers.view'],
    ];

    public function down() : void
    {
        foreach (self::GRANTS as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            foreach ($permissions as $name) {
                if ($role && $role->hasPermissionTo($name)) {
                    $role->revokePermissionTo($name);
                }
            }
        }

        Role::where('name', 'customer')->where('guard_name', 'web')->first()?->givePermissionTo('sellers.view');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function up() : void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['customers.manage-own', 'treasurers.manage-own', 'treasurers.view'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (self::GRANTS as $roleName => $permissions) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo($permissions);
        }

        $customer = Role::where('name', 'customer')->where('guard_name', 'web')->first();
        if ($customer && $customer->hasPermissionTo('sellers.view')) {
            $customer->revokePermissionTo('sellers.view');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
