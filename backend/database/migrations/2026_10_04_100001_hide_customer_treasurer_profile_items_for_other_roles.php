<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\SidebarMenuVisibility;
use Illuminate\Database\Migrations\Migration;

/**
 * Migración de datos (no seeder): oculta "Mi Cliente" / "Mi Tesorería"
 * (my_account.my_profile_customer / my_profile_treasurer) a todo rol que no
 * sea el dueño — mismas exclusiones que SidebarMenuVisibilitySeeder.
 * Importa sobre todo para superadmin, que tiene todos los permisos y sin esto
 * vería ítems que le dan 404 (no tiene ficha Customer/Treasurer propia).
 * Producción no vuelve a correr seeders (solo `migrate --force`).
 */
return new class extends Migration
{
    private const EXCLUSIONS = [
        'my_account.my_profile_customer'  => ['admin', 'commerce', 'player', 'seller', 'subsidiary', 'superadmin', 'treasurer'],
        'my_account.my_profile_treasurer' => ['admin', 'commerce', 'customer', 'player', 'seller', 'subsidiary', 'superadmin'],
    ];

    public function up(): void
    {
        foreach (self::EXCLUSIONS as $itemKey => $roleNames) {
            foreach (Role::whereIn('name', $roleNames)->get(['id']) as $role) {
                SidebarMenuVisibility::updateOrCreate(
                    ['item_key' => $itemKey, 'role_id' => $role->id],
                    ['is_visible' => false],
                );
            }
        }
    }

    public function down(): void
    {
        SidebarMenuVisibility::whereIn('item_key', array_keys(self::EXCLUSIONS))->delete();
    }
};
