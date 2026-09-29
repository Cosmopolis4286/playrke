<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\SidebarMenuVisibility;
use Illuminate\Database\Seeder;

/**
 * Siembra el estado de visibilidad por rol vigente en producción — snapshot
 * fijo capturado el 2026-09-04, cuando $menuStructure dejó de tener
 * `exclude_roles` (las exclusiones ahora solo existen en esta tabla, ver
 * SidebarMenuService::isVisibleForRoles()). Antes este seeder derivaba las
 * filas leyendo `exclude_roles` de $menuStructure en tiempo de ejecución;
 * ahora que esa clave no existe en el código, el snapshot es la única forma
 * de reproducir el comportamiento vigente en una instalación nueva
 * (`migrate:fresh --seed`, CI, testing).
 *
 * No siembra filas para combinaciones sin exclusión — semántica "sin fila =
 * visible" (igual que ModuleSeeder). Es aditivo por (item_key, role_id) vía
 * updateOrCreate: correrlo de nuevo sobre una BD que ya tiene overrides
 * manuales del admin UI no los revierte.
 */
class SidebarMenuVisibilitySeeder extends Seeder
{
    /**
     * item_key => [role names excluidos].
     *
     * @var array<string, string[]>
     */
    private const EXCLUSIONS = [
        'games_bingo.my_games'          => ['admin', 'commerce', 'customer', 'seller', 'subsidiary', 'superadmin'],
        'games_bingo.bingo_cards_sold'  => ['player'],
        'business.exchange_rates'       => ['player', 'seller'],
        // subsidiary NO se excluye: el fix 9201ced9 (2026-09-01) le agregó
        // soporte real en ManagerSellerContextService::subsidiaryScopeCondition()
        // (filtra por subsidiary_id, ve solo sus propios vendedores) pero
        // nunca se actualizó esta exclusión — la ruta /sellers funcionaba
        // perfectamente para subsidiary desde entonces, solo el sidebar
        // nunca ofrecía la entrada.
        // customer excluido: no puede tener sellers (ver ManagerSellerPolicy)
        'business.my_sellers'           => ['admin', 'customer', 'player', 'seller', 'superadmin'],
        'business.my_subsidiaries'      => ['player', 'seller', 'superadmin'],
        // my_account.* — antes vivían bajo 'business.*' (ver comentario de
        // clase); el refactor 3c945746 ("Mi Cuenta" separado de "Negocio")
        // movió estos 4 items de parent pero nunca migró estas filas, así
        // que quedaron apuntando a keys huérfanas sin efecto — admin y
        // superadmin volvieron a ver "Mi Perfil"/"Mi Comercio"/"Mi Agencia"
        // sin tener nunca una ficha Seller/Commerce/Subsidiary propia,
        // causando 404 al entrar (ver AdminProfileAccessService). commerce
        // NO se excluye de my_profile_commerce/commerce_economy_summary (sí
        // tiene ficha propia); subsidiary NO se excluye de
        // my_profile_subsidiary (ídem); seller NO se excluye de
        // my_profile_seller (ídem) — todo lo demás replica la exclusión
        // original de 'business.*'.
        'my_account.my_profile_subsidiary'  => ['admin', 'commerce', 'player', 'seller', 'superadmin'],
        'my_account.my_profile_commerce'    => ['admin', 'player', 'seller', 'subsidiary', 'superadmin'],
        'my_account.commerce_economy_summary' => ['admin', 'player', 'seller', 'subsidiary', 'superadmin'],
        'my_account.my_profile_seller'      => ['admin', 'commerce', 'player', 'subsidiary', 'superadmin'],
        'business.my_external_players'  => ['admin', 'player', 'superadmin'],
        'business.prizes'               => ['player', 'seller'],
        'business.win_events'           => ['commerce', 'customer', 'player', 'seller'],
        'system.app-parameters'         => ['commerce', 'customer', 'player', 'seller'],
        'system.audit'                  => ['commerce', 'customer', 'player', 'seller'],
        'system.kyc'                    => ['commerce', 'customer', 'player', 'seller'],
        'system.logs'                   => ['commerce', 'customer', 'player', 'seller'],
        'system.jobs'                   => ['commerce', 'customer', 'player', 'seller'],
    ];

    public function run(): void
    {
        // name (normalizado a minúsculas por Role::setNameAttribute) => id.
        $roleIdsByName = Role::pluck('id', 'name')->all();

        $rows = 0;

        foreach (self::EXCLUSIONS as $itemKey => $excludedRoles) {
            foreach ($excludedRoles as $roleName) {
                $roleId = $roleIdsByName[$roleName] ?? null;

                if ($roleId === null) {
                    $this->command->warn("  ⚠ Rol '{$roleName}' no encontrado (item: {$itemKey})");
                    continue;
                }

                SidebarMenuVisibility::updateOrCreate(
                    ['item_key' => $itemKey, 'role_id' => $roleId],
                    ['is_visible' => false]
                );
                $rows++;
            }
        }

        $this->command->info("✔ SidebarMenuVisibilitySeeder: {$rows} filas de exclusión sembradas.");
    }
}
