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
                // 'roles.view', 'roles.assign', // Solo superadmin gestiona roles
                'commerces.view', 'commerces.create', 'commerces.edit', 'commerces.approve',
                'sellers.view', 'sellers.create', 'sellers.edit',
                'customers.view', 'customers.create', 'customers.edit',
                'treasury.view', 'treasury.reports',
                'exchange.view', 'exchange.manage',
                'games.view', 'games.manage',
                'games.bingo', // Acceso a juegos de bingo
                'games.bingo.winners', 'games.bingo.sales_report', // Ganadores y reporte de ventas de bingo
                'reports.view', 'reports.generate', 'reports.export', 'reports.financial', 'reports.gaming',
                'audit.view',
                'kyc.view', 'kyc.manage',
                'win-events.manage',
                'settings.manage', // Configuración general de la plataforma
                // Opciones independientes
                'directory.view', 'directory.search',
                'prizes.view', 'prizes.create', 'prizes.edit', 'prizes.delete', 'prizes.assign',
                'wallet.movements.view', 'wallet.movements.manage', // Gestión de movimientos de billetera
                'movement-types.view', 'movement-types.manage', // Catálogo de tipos de movimiento
                // Trivia - Gestión completa (admin gestiona salas/preguntas pero NO juega como player)
                'games.trivia.view',
                'games.trivia.rooms.view', 'games.trivia.rooms.create', 'games.trivia.rooms.edit', 'games.trivia.rooms.delete', 'games.trivia.rooms.manage',
                'games.trivia.questions.view', 'games.trivia.questions.create', 'games.trivia.questions.edit', 'games.trivia.questions.delete', 'games.trivia.questions.manage',
                'games.trivia.games.view', 'games.trivia.games.manage',
                'games.trivia.leaderboard.view', 'games.trivia.stats.view', 'games.trivia.stats.export',
            ];
            $admin->givePermissionTo($adminPermissions);
            $this->command->info('✅ Permisos asignados a Admin');
        }

        // Commerce: Gestión de su negocio
        if ($commerce) {
            $commercePermissions = [
                'sellers.view', 'sellers.create', 'sellers.edit', 'sellers.delete',
                'customers.view', 'customers.create', 'customers.edit',
                'games.bingo', // Acceso a juegos de bingo
                'games.bingo.winners', 'games.bingo.sales_report', // Ganadores y reporte de ventas de bingo
                'reports.view', 'reports.generate', 'reports.gaming',
                // Opciones independientes
                'directory.view', 'directory.search',
                'prizes.view', 'prizes.create', 'prizes.edit',
                // Trivia - Solo ver partidas y leaderboard (no jugar - es un rol de gestión)
                'games.trivia.view',
                'games.trivia.games.view',
                'games.trivia.leaderboard.view',
            ];
            $commerce->givePermissionTo($commercePermissions);
            $this->command->info('✅ Permisos asignados a Commerce');
        }

        // Seller: Gestión de ventas
        if ($seller) {
            $sellerPermissions = [
                'games.bingo', // Acceso a juegos de bingo
                'reports.view', 'reports.gaming',
                // Trivia - Solo ver partidas y leaderboard (no jugar - es un rol de gestión)
                'games.trivia.view',
                'games.trivia.games.view',
                'games.trivia.leaderboard.view',
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
                // Trivia - Ver estadísticas y leaderboard (rol financiero)
                'games.trivia.view',
                'games.trivia.leaderboard.view',
                'games.trivia.stats.view', 'games.trivia.stats.export',
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
                'games.bingo', // Acceso a juegos de bingo (guard 'web')
                'directory.view', 'directory.search', // Acceso al directorio
                // Trivia - Puede jugar y ver leaderboard
                'games.trivia.view', 'games.trivia.play', 'games.trivia.leaderboard.view',
            ];
            $customer->givePermissionTo($customerPermissions);
            $this->command->info('✅ Permisos asignados a Customer');
        }

        // Player: Rol de jugador — acceso a juegos
        if ($player) {
            $playerPermissions = [
                'games.bingo', // Acceso a juegos de bingo
                'games.bingo.game_rooms', // Acceso a salas de bingo
                'wallet.view', // Acceso a ver billetera
                // Trivia - Principal rol para jugar
                'games.trivia.view',
                'games.trivia.play',          // Lobby + unirse a salas (role:player en backend)
                'games.trivia.leaderboard.view',
                'games.trivia.games.view',    // Ver historial de partidas propias
                'games.trivia.stats.view',    // Ver estadísticas propias
            ];
            $player->givePermissionTo($playerPermissions);
            $this->command->info('✅ Permisos asignados a Player');
        }

        $this->command->info('✅ Asignación de permisos a roles completada');
    }
}
