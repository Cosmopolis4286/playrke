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
        'business.my_sellers'           => ['admin', 'player', 'seller', 'subsidiary', 'superadmin'],
        'business.my_subsidiaries'      => ['player', 'seller', 'superadmin'],
        'business.my_profile_subsidiary' => ['admin', 'commerce', 'player', 'seller', 'superadmin'],
        'business.my_profile_commerce'  => ['admin', 'player', 'seller', 'subsidiary', 'superadmin'],
        'business.my_profile_seller'    => ['admin', 'commerce', 'player', 'subsidiary', 'superadmin'],
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
