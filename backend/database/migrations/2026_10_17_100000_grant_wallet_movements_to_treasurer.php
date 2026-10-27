<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
 * Migración de datos (no seeder): tesorería gestiona la cola de recargas y
 * retiros — wallet.movements.view (ver) y wallet.movements.manage
 * (aprobar/rechazar/tomar caso). Tenía treasury.approve.transactions, pero
 * ningún middleware lo consulta: la cola (routes/modules/wallets.php) pide
 * wallet.movements.*, así que tesorería nunca pudo entrar.
 *
 * Producción no vuelve a correr seeders tras el deploy inicial (solo
 * `migrate --force`), por eso va como migración además de
 * RolePermissionSeeder. Idempotente: givePermissionTo() no falla si el rol
 * ya tiene el permiso.
 */
return new class extends Migration
{
    private const PERMISSIONS = ['wallet.movements.view', 'wallet.movements.manage'];

    public function down() : void
    {
        $treasurer = Role::where('name', 'treasurer')->where('guard_name', 'web')->first();

        if (! $treasurer) {
            return;
        }

        foreach (self::PERMISSIONS as $name) {
            if ($treasurer->hasPermissionTo($name)) {
                $treasurer->revokePermissionTo($name);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function up() : void
    {
        $treasurer = Role::where('name', 'treasurer')->where('guard_name', 'web')->first();

        if (! $treasurer) {
            return;
        }

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $treasurer->givePermissionTo(self::PERMISSIONS);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
