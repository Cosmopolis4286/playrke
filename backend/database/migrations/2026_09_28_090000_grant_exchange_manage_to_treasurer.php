<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

/*
 * Migración de datos (no seeder): otorga exchange.manage a 'treasurer' y
 * revoca el permiso huérfano 'exchange.update' que tenía en su lugar.
 *
 * RolePermissionSeeder::run() asignaba históricamente 'exchange.update' a
 * treasurer, pero ese permiso nunca fue consultado por ningún middleware —
 * routes/modules/exchange-rate.php usa multiPermission:exchange.view,
 * exchange.manage. Mientras todas las acciones del módulo compartían ese
 * único middleware combinado, treasurer pasaba igual (view bastaba). Al
 * separar exchange.manage por acción de escritura (store/update/delete/
 * toggleStatus/cronjobs), treasurer se quedó sin poder editar la tasa
 * OFICIAL pese a que ExchangeRateCrudService::resolveContext() lo trata
 * exactamente igual que admin/superadmin desde siempre — el propio dominio
 * ya lo autorizaba, solo el permiso real nunca coincidió.
 *
 * Producción no vuelve a correr seeders tras el deploy inicial (solo
 * `migrate --force`, ver deploy.sh) — sin esta migración, un treasurer en
 * producción perdería silenciosamente la capacidad de editar tasas hasta el
 * próximo `db:seed` manual.
 *
 * Idempotente: givePermissionTo()/revokePermissionTo() no fallan si el
 * estado ya es el deseado.
 */
return new class extends Migration
{
    public function down() : void
    {
        $treasurer = Role::where('name', 'treasurer')->where('guard_name', 'web')->first();

        if (! $treasurer) {
            return;
        }

        $treasurer->revokePermissionTo('exchange.manage');
        $treasurer->givePermissionTo('exchange.update');
    }

    public function up() : void
    {
        $treasurer = Role::where('name', 'treasurer')->where('guard_name', 'web')->first();

        if (! $treasurer) {
            return;
        }

        $treasurer->givePermissionTo('exchange.manage');

        if ($treasurer->hasPermissionTo('exchange.update')) {
            $treasurer->revokePermissionTo('exchange.update');
        }
    }
};
