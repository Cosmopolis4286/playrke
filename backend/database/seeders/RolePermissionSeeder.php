<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener todos los roles
        $superadmin = Role::where('name', 'superadmin')->where('guard_name', 'web')->first();
        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        $commerce = Role::where('name', 'commerce')->where('guard_name', 'web')->first();
        $seller = Role::where('name', 'seller')->where('guard_name', 'web')->first();
        $treasurer = Role::where('name', 'treasurer')->where('guard_name', 'web')->first();
        $provider = Role::where('name', 'provider')->where('guard_name', 'api')->first();
        $customer = Role::where('name', 'customer')->where('guard_name', 'api')->first();
        $player = Role::where('name', 'player')->where('guard_name', 'web')->first();

        // SuperAdmin: Todos los permisos (solo web guard)
        if ($superadmin) {
            // Solo asignar permisos con guard 'web' al rol SuperAdmin
            $webPermissions = Permission::where('guard_name', 'web')->get();
            $superadmin->givePermissionTo($webPermissions);
            $this->command->info('✅ Permisos asignados a SuperAdmin');
        }

        // Admin: Casi todos los permisos excepto los de SuperAdmin
        if ($admin) {
            $adminPermissions = [
                'users.view', 'users.create', 'users.edit', 'users.manage',
                'roles.view', 'roles.assign',
                'commerces.view', 'commerces.create', 'commerces.edit', 'commerces.approve',
                'sellers.view', 'sellers.create', 'sellers.edit',
                'customers.view', 'customers.create', 'customers.edit',
                'treasury.view', 'treasury.reports',
                'exchange.view', 'exchange.manage',
                'games.view', 'games.manage',
                'game.bingo', // Acceso a juegos de bingo
                'reports.view', 'reports.generate', 'reports.export', 'reports.financial', 'reports.gaming',
                'audit.view',
                // Opciones independientes
                'directory.view', 'directory.search',
                'prizes.view', 'prizes.create', 'prizes.edit', 'prizes.delete', 'prizes.assign',
            ];
            $admin->givePermissionTo($adminPermissions);
            $this->command->info('✅ Permisos asignados a Admin');
        }

        // Commerce: Gestión de su negocio
        if ($commerce) {
            $commercePermissions = [
                'sellers.view', 'sellers.create', 'sellers.edit', 'sellers.delete',
                'customers.view', 'customers.create', 'customers.edit',
                'game.bingo', // Acceso a juegos de bingo
                'reports.view', 'reports.generate', 'reports.gaming',
                // Opciones independientes
                'directory.view', 'directory.search',
                'prizes.view', 'prizes.create', 'prizes.edit',
            ];
            $commerce->givePermissionTo($commercePermissions);
            $this->command->info('✅ Permisos asignados a Commerce');
        }

        // Seller: Gestión de ventas y clientes
        if ($seller) {
            $sellerPermissions = [
                'customers.view', 'customers.create', 'customers.edit',
                'game.bingo', // Acceso a juegos de bingo
                'reports.view', 'reports.gaming',
            ];
            $seller->givePermissionTo($sellerPermissions);
            $this->command->info('✅ Permisos asignados a Seller');
        }

        // Treasurer: Gestión financiera
        if ($treasurer) {
            $treasurerPermissions = [
                'treasury.view', 'treasury.manage', 'treasury.approve.transactions', 'treasury.reports',
                'exchange.view', 'exchange.update',
                'reports.view', 'reports.generate', 'reports.export', 'reports.financial',
                // Opciones independientes
                'directory.view', 'directory.search',
                'prizes.view',
            ];
            $treasurer->givePermissionTo($treasurerPermissions);
            $this->command->info('✅ Permisos asignados a Treasurer');
        }

        // Provider: Acceso API limitado
        if ($provider) {
            $providerPermissions = [
                'customers.api.access', // Este permiso usa guard 'api'
            ];
            // Obtener permisos con guard 'api' para el rol provider
            $apiPermissions = Permission::where('guard_name', 'api')
                ->whereIn('name', $providerPermissions)
                ->get();
            $provider->givePermissionTo($apiPermissions);
            $this->command->info('✅ Permisos asignados a Provider');
        }

        // Customer: Acceso web limitado
        if ($customer) {
            $customerPermissions = [
                'game.bingo', // Acceso a juegos de bingo (guard 'web')
                'directory.view', 'directory.search', // Acceso al directorio
            ];
            $customer->givePermissionTo($customerPermissions);
            $this->command->info('✅ Permisos asignados a Customer');
        }

        // Player: Solo acceso a juegos de bingo
        if ($player) {
            $playerPermissions = [
                'game.bingo', // Acceso a juegos de bingo
                'wallet.view', // Acceso a ver billetera
            ];
            $player->givePermissionTo($playerPermissions);
            $this->command->info('✅ Permisos asignados a Player');
        }

        $this->command->info('✅ Asignación de permisos a roles completada');
    }
}
