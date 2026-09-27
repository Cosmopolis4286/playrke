<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\SidebarMenuVisibility;
use Illuminate\Database\Migrations\Migration;

/**
 * Migración de datos (no seeder): elimina la exclusión de 'subsidiary' en
 * business.my_sellers ("Mis Vendedores").
 *
 * El fix 9201ced9 (2026-09-01, "subsidiary no veía sus propios vendedores")
 * agregó soporte real a subsidiary en
 * ManagerSellerContextService::subsidiaryScopeCondition() — filtra
 * correctamente por subsidiary_id y la ruta /sellers ya funciona end-to-end
 * para este rol (verificado: carga sin error, muestra solo sus propios
 * vendedores) — pero la fila de exclusión en sidebar_menu_visibilities
 * (sembrada por SidebarMenuVisibilitySeeder antes de ese fix) nunca se quitó,
 * dejando la funcionalidad invisible en el menú pese a estar completamente
 * operativa por URL directa.
 *
 * Producción no vuelve a correr seeders tras el deploy inicial (solo
 * `migrate --force`, ver deploy.sh) — sin esta migración, la fila quedaría
 * así indefinidamente y SidebarMenuVisibilitySeeder (ya corregido) nunca la
 * tocaría en una BD existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        $subsidiary = Role::where('name', 'subsidiary')->first();

        if (!$subsidiary) {
            return;
        }

        SidebarMenuVisibility::where('item_key', 'business.my_sellers')
            ->where('role_id', $subsidiary->id)
            ->delete();
    }

    public function down(): void
    {
        $subsidiary = Role::where('name', 'subsidiary')->first();

        if (!$subsidiary) {
            return;
        }

        SidebarMenuVisibility::updateOrCreate(
            ['item_key' => 'business.my_sellers', 'role_id' => $subsidiary->id],
            ['is_visible' => false],
        );
    }
};
