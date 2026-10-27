<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use App\Models\SidebarMenuVisibility;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/*
 * Migración de datos (no seeder; producción solo corre `migrate --force`):
 * tasas de cambio para la red.
 *  - commerce: exchange.view + exchange.manage → entra a "Tasa de Cambio" y
 *    fija su tasa propia (commerces_exchange_rates), que el dominio ya usaba
 *    en las ventas de sus salas pero que no podía ver ni editar.
 *  - exchange.view-network (nuevo): widget "Tasa de cambio de tu red" en los
 *    tableros de comercializadora, agencia, vendedor, cliente y plataforma.
 *  - Se asegura que el ítem "Negocio › Tasa de Cambio" no esté oculto para
 *    commerce (antes no tenía el permiso, el ítem nunca le aparecía).
 * Idempotente.
 */
return new class extends Migration
{
    private const COMMERCE_PERMISSIONS = ['exchange.view', 'exchange.manage'];
    private const MENU_ITEM = 'business.exchange_rates';
    private const NETWORK_PERMISSION = 'exchange.view-network';
    private const NETWORK_ROLES = ['commerce', 'subsidiary', 'seller', 'customer', 'admin', 'superadmin', 'treasurer'];

    public function down() : void
    {
        foreach (Role::whereIn('name', self::NETWORK_ROLES)->where('guard_name', 'web')->get() as $role) {
            if ($role->hasPermissionTo(self::NETWORK_PERMISSION)) {
                $role->revokePermissionTo(self::NETWORK_PERMISSION);
            }
        }

        $commerce = Role::where('name', 'commerce')->where('guard_name', 'web')->first();
        foreach (self::COMMERCE_PERMISSIONS as $name) {
            if ($commerce?->hasPermissionTo($name)) {
                $commerce->revokePermissionTo($name);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function up() : void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(
            ['name' => self::NETWORK_PERMISSION, 'guard_name' => 'web'],
            ['description' => 'Ver la tasa de cambio vigente de su red (widget del tablero)'],
        );
        foreach (self::COMMERCE_PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (Role::whereIn('name', self::NETWORK_ROLES)->where('guard_name', 'web')->get() as $role) {
            $role->givePermissionTo(self::NETWORK_PERMISSION);
        }

        $commerce = Role::where('name', 'commerce')->where('guard_name', 'web')->first();
        if ($commerce) {
            $commerce->givePermissionTo(self::COMMERCE_PERMISSIONS);
            SidebarMenuVisibility::where('item_key', self::MENU_ITEM)->where('role_id', $commerce->id)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
