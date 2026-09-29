<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\SidebarMenuVisibility;
use Illuminate\Database\Migrations\Migration;

/**
 * Migración de datos (no seeder): oculta business.my_sellers ("Mis
 * Vendedores") para 'customer'.
 *
 * Un customer no puede tener sellers (rama independiente, ver
 * ManagerSellerPolicy::requiredRole() / ManagerSellerService::
 * resolveManagerForUser()) y ya no tiene acceso a las rutas /sellers — la
 * entrada del sidebar solo lo llevaba a un 403. Producción no vuelve a correr
 * seeders tras el deploy inicial (solo `migrate --force`), por eso además de
 * SidebarMenuVisibilitySeeder va esta migración. Mismo patrón que
 * 2026_09_27_130000_remove_subsidiary_exclusion_from_my_sellers_sidebar_item.
 */
return new class extends Migration
{
    public function up(): void
    {
        $customer = Role::where('name', 'customer')->first();

        if (!$customer) {
            return;
        }

        SidebarMenuVisibility::updateOrCreate(
            ['item_key' => 'business.my_sellers', 'role_id' => $customer->id],
            ['is_visible' => false],
        );
    }

    public function down(): void
    {
        $customer = Role::where('name', 'customer')->first();

        if (!$customer) {
            return;
        }

        SidebarMenuVisibility::where('item_key', 'business.my_sellers')
            ->where('role_id', $customer->id)
            ->delete();
    }
};
