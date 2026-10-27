<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\SidebarMenuVisibility;
use Illuminate\Database\Migrations\Migration;

/*
 * Migración de datos (no seeder): renombra las item_key huérfanas que dejó
 * el refactor 3c945746 ("Mi Cuenta" separado de "Negocio") en
 * sidebar_menu_visibilities.
 *
 * Ese commit movió my_profile_subsidiary/my_profile_commerce/
 * my_profile_seller del padre 'business' al nuevo padre 'my_account' en
 * SidebarMenuService::$menuStructure, pero nunca migró las filas de esta
 * tabla — SidebarMenuService::isVisibleForRoles() arma la key como
 * "{parentKey}.{subKey}", así que las filas viejas ('business.my_profile_*')
 * quedaron sin efecto: admin y superadmin volvieron a ver "Mi Perfil"/
 * "Mi Comercio"/"Mi Agencia" en el sidebar sin tener nunca una ficha
 * Seller/Commerce/Subsidiary propia, resultando en 404 al entrar (ver
 * AdminProfileAccessService, que resuelve target_user_id).
 *
 * También siembra la exclusión para 'my_account.commerce_economy_summary'
 * (item nuevo del mismo refactor, sin key vieja que migrar — mismo criterio
 * que my_profile_commerce, con quien comparte permiso commerces.manage-own).
 *
 * Producción no vuelve a correr seeders tras el deploy inicial (solo
 * `migrate --force`, ver deploy.sh) — sin esta migración, las 15 filas
 * huérfanas quedarían así indefinidamente y SidebarMenuVisibilitySeeder
 * (ya actualizado con las keys nuevas) nunca las tocaría en una BD
 * existente.
 */
return new class extends Migration
{
    private const KEY_RENAMES = [
        'business.my_profile_subsidiary' => 'my_account.my_profile_subsidiary',
        'business.my_profile_commerce' => 'my_account.my_profile_commerce',
        'business.my_profile_seller' => 'my_account.my_profile_seller',
    ];

    /** item_key => [role names excluidos] — mismo formato que SidebarMenuVisibilitySeeder::EXCLUSIONS. */
    private const NEW_EXCLUSIONS = [
        'my_account.commerce_economy_summary' => ['admin', 'player', 'seller', 'subsidiary', 'superadmin'],
    ];

    public function down() : void
    {
        foreach (self::KEY_RENAMES as $oldKey => $newKey) {
            SidebarMenuVisibility::where('item_key', $newKey)
                ->get()
                ->each(function(SidebarMenuVisibility $row) use ($oldKey) : void {
                    SidebarMenuVisibility::updateOrCreate(
                        ['item_key' => $oldKey, 'role_id' => $row->role_id],
                        ['is_visible' => $row->is_visible],
                    );
                    $row->delete();
                });
        }

        SidebarMenuVisibility::where('item_key', 'my_account.commerce_economy_summary')->delete();
    }

    public function up() : void
    {
        foreach (self::KEY_RENAMES as $oldKey => $newKey) {
            // updateOrCreate por (item_key, role_id) — si por algún motivo ya
            // existe una fila con la key nueva (ej. re-ejecución tras un
            // rollback parcial), no duplica; y no arrastra sort_order viejo
            // porque estos items nunca tuvieron override de orden.
            SidebarMenuVisibility::where('item_key', $oldKey)
                ->get()
                ->each(function(SidebarMenuVisibility $row) use ($newKey) : void {
                    SidebarMenuVisibility::updateOrCreate(
                        ['item_key' => $newKey, 'role_id' => $row->role_id],
                        ['is_visible' => $row->is_visible],
                    );
                    $row->delete();
                });
        }

        $roleIdsByName = Role::pluck('id', 'name')->all();

        foreach (self::NEW_EXCLUSIONS as $itemKey => $excludedRoles) {
            foreach ($excludedRoles as $roleName) {
                $roleId = $roleIdsByName[$roleName] ?? null;

                if ($roleId === null) {
                    continue;
                }

                SidebarMenuVisibility::updateOrCreate(
                    ['item_key' => $itemKey, 'role_id' => $roleId],
                    ['is_visible' => false],
                );
            }
        }
    }
};
