<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/*
 * Migración de datos (no seeder): asigna games.bingo.sales_report y
 * games.bingo.winners al rol 'seller' — producción no vuelve a correr
 * seeders (deploy.sh solo ejecuta `migrate --force`), mismo criterio que
 * 2026_09_27_000001_add_sidebar_and_dashboard_visibility_permissions.
 *
 * Seguro de conceder solo porque BingoReportController ahora limita al
 * vendedor a SUS propias ventas (seller_user_id) en Cartones, Ganadores y
 * Reporte de Ventas — sin ese scope, estos permisos le habrían expuesto las
 * ventas de todo su comercio y de salas PUBLIC ajenas.
 *
 * Idempotente: firstOrCreate() en los permisos (ya existen vía
 * PermissionSeeder, pero no se asume) y givePermissionTo(), que no duplica
 * la fila pivote ni toca otros permisos del rol.
 */
return new class extends Migration
{
    private const PERMISSIONS = ['games.bingo.sales_report', 'games.bingo.winners'];

    public function down() : void
    {
        // Solo se revoca al seller — los permisos en sí los usan otros roles.
        $seller = Role::where('name', 'seller')->where('guard_name', 'web')->first();
        if ($seller) {
            foreach (self::PERMISSIONS as $name) {
                if ($seller->hasPermissionTo($name)) {
                    $seller->revokePermissionTo($name);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function up() : void
    {
        // Antes de resolver permisos por nombre: givePermissionTo() los busca
        // en el cache de Spatie, que puede no reflejar la BD actual.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $seller = Role::where('name', 'seller')->where('guard_name', 'web')->first();
        if ($seller) {
            $seller->givePermissionTo(self::PERMISSIONS);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
