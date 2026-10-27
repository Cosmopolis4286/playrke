<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use App\Models\SidebarMenuVisibility;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/*
 * Migración de datos: el cliente API gestiona su tasa propia
 * (customers_exchange_rates), que ahora sí usan las ventas de sus salas
 * (ExchangeRateDomainService: propia ACTIVA o, si no, la oficial).
 * Mismo criterio que 2026_10_19_100000 para la comercializadora. Idempotente.
 */
return new class extends Migration
{
    private const MENU_ITEM = 'business.exchange_rates';
    private const PERMISSIONS = ['exchange.view', 'exchange.manage'];

    public function down() : void
    {
        $customer = Role::where('name', 'customer')->where('guard_name', 'web')->first();
        foreach (self::PERMISSIONS as $name) {
            if ($customer?->hasPermissionTo($name)) {
                $customer->revokePermissionTo($name);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function up() : void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $customer = Role::where('name', 'customer')->where('guard_name', 'web')->first();
        if ($customer) {
            $customer->givePermissionTo(self::PERMISSIONS);
            SidebarMenuVisibility::where('item_key', self::MENU_ITEM)->where('role_id', $customer->id)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
