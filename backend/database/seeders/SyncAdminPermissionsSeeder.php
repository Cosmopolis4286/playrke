<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeder temporal para sincronizar permisos del rol admin
 */
class SyncAdminPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();

        if (!$admin) {
            $this->command->error('❌ Rol admin no encontrado');
            return;
        }

        // Limpiar permisos existentes del admin
        $admin->permissions()->detach();
        $this->command->info('🧹 Permisos anteriores eliminados del admin');

        // Lista completa de permisos para admin
        $adminPermissionNames = [
            'users.view', 'users.create', 'users.edit', 'users.manage',
            'roles.view', 'roles.assign',
            'commerces.view', 'commerces.create', 'commerces.edit', 'commerces.approve',
            'sellers.view', 'sellers.create', 'sellers.edit',
            'customers.view', 'customers.create', 'customers.edit',
            'treasury.view', 'treasury.reports',
            'exchange.view', 'exchange.manage',
            'games.view', 'games.create', 'games.edit', 'games.delete', 'games.manage',
            'game.bingo',
            'game.bingo.game_rooms',
            'reports.view', 'reports.generate', 'reports.export', 'reports.financial', 'reports.gaming',
            'audit.view',
            'directory.view', 'directory.search',
            'prizes.view', 'prizes.create', 'prizes.edit', 'prizes.delete', 'prizes.assign',
            'wallet.view', 'wallet.deposit', 'wallet.withdraw', 'wallet.movements',
        ];

        // Obtener los permisos que existen en la BD
        $permissions = Permission::whereIn('name', $adminPermissionNames)
            ->where('guard_name', 'web')
            ->get();

        $foundNames = $permissions->pluck('name')->toArray();
        $missingNames = array_diff($adminPermissionNames, $foundNames);

        if (!empty($missingNames)) {
            $this->command->warn('⚠️  Permisos no encontrados en BD: ' . implode(', ', $missingNames));
        }

        // Asignar permisos
        $admin->givePermissionTo($permissions);

        $this->command->info('✅ ' . count($permissions) . ' permisos asignados al admin');
        $this->command->info('Permisos asignados: ' . $permissions->pluck('name')->implode(', '));

        // Verificar
        $admin->refresh();
        $this->command->info('📝 Total permisos del admin: ' . $admin->permissions->count());
    }
}
