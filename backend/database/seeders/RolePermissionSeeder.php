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
        $subsidiary = Role::where('name', 'subsidiary')->where('guard_name', 'web')->first();
        $seller = Role::where('name', 'seller')->where('guard_name', 'web')->first();
        $treasurer = Role::where('name', 'treasurer')->where('guard_name', 'web')->first();
        $provider = Role::where('name', 'provider')->where('guard_name', 'api')->first();
        $customer = Role::where('name', 'customer')->where('guard_name', 'web')->first();
        $player = Role::where('name', 'player')->where('guard_name', 'web')->first();
        $externalPlayer = Role::where('name', 'external_player')->where('guard_name', 'web')->first();

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
                'users.view', 'users.create', 'users.edit', 'users.delete', 'users.manage',
                // 'roles.view', 'roles.assign', // Solo superadmin gestiona roles
                'commerces.view', 'commerces.create', 'commerces.edit', 'commerces.delete', 'commerces.approve',
                'subsidiaries.view', 'subsidiaries.create', 'subsidiaries.edit', 'subsidiaries.delete',
                'sellers.view', 'sellers.create', 'sellers.edit',
                'customers.view', 'customers.create', 'customers.edit',
                'treasurers.view', // Vista admin de "Mi Tesorería" (target_user_id)
                'treasury.view', 'treasury.reports',
                'exchange.view', 'exchange.manage', 'exchange.view-network',
                'games.view', 'games.manage',
                'games.bingo', // Acceso a juegos de bingo
                'games.bingo.manage', // Gestionar salas de bingo (crear/editar/eliminar) — antes solo cubierto por games.manage a nivel de ruta, pero el ítem del sidebar y la Policy ahora exigen este permiso explícito
                'games.bingo.game_rooms', // Ver/comprar en salas de bingo — antes ausente: admin tenía "Gestionar Salas" pero no la vista de comprador, útil para soporte/QA sin necesitar otra cuenta
                'games.bingo.winners', 'games.bingo.sales_report', // Ganadores y reporte de ventas de bingo
                'games.bingo.payments.review', // Cola de revisión de pagos de ventas a externos (ve todos)
                'reports.view', 'reports.generate', 'reports.export', 'reports.financial', 'reports.gaming',
                'audit.view',
                'kyc.view', 'kyc.manage',
                'anticheat.view', 'anticheat.manage',
                'win-events.manage',
                'app-parameters.manage', // Configuración general de la plataforma
                // Opciones independientes
                'directory.view', 'directory.search',
                'prizes.view', 'prizes.create', 'prizes.edit', 'prizes.delete', 'prizes.assign',
                'wallet.movements.view', 'wallet.movements.manage', // Gestión de movimientos de billetera
                'movement-types.view', 'movement-types.manage', // Catálogo de tipos de movimiento
                'wallet-limits.view', 'wallet-limits.manage', // Límites de depósito/retiro de billetera
                'external-players.view', 'external-players.create', 'external-players.manage', 'external-players.block', 'external-players.export',
                'marketplace.listings.view', 'marketplace.orders.view', 'marketplace.manage', // Moderación de marketplace
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
                'commerces.manage-own',
                'commissions.view-own', // "Mis Comisiones": red + comisión de sala
                'exchange.view', 'exchange.manage', // Su tasa propia (commerces_exchange_rates)
                'exchange.view-network', // Widget de tasa en el tablero
                'sellers.view', 'sellers.create', 'sellers.edit', 'sellers.delete',
                'subsidiaries.view', 'subsidiaries.create', 'subsidiaries.edit', 'subsidiaries.delete',
                'customers.view', 'customers.create', 'customers.edit',
                'games.bingo', // Acceso a juegos de bingo
                'games.bingo.manage', // Gestionar salas de bingo (crear/editar/eliminar/cambiar estado) — antes ausente: el comercio podía jugar/ver reportes pero no administrar salas pese a que la ruta ya lo permitía a nivel de rol
                'games.bingo.winners', 'games.bingo.sales_report', // Ganadores y reporte de ventas de bingo
                'games.bingo.payments.review', // Revisa los pagos de SU red (scope en BingoCardPaymentReviewController)
                'reports.view', 'reports.generate', 'reports.gaming',
                // Opciones independientes
                'directory.view', 'directory.search',
                'prizes.view', 'prizes.create', 'prizes.edit',
                'external-players.view', 'external-players.create', 'external-players.manage', 'external-players.block',
                // Trivia - Solo ver partidas y leaderboard (no jugar - es un rol de gestión)
                'games.trivia.view',
                'games.trivia.games.view',
                'games.trivia.leaderboard.view',
            ];
            $commerce->givePermissionTo($commercePermissions);
            $this->command->info('✅ Permisos asignados a Commerce');
        }

        // Subsidiary: Sucursal de un comercio. Por defecto NO gestiona salas
        // (ni de bingo ni de trivia): las crea su comercializadora; la agencia
        // gestiona su red de vendedores y consulta reportes/ganadores. A una
        // agencia concreta se le puede conceder games.bingo.manage como
        // permiso individual (UserSpecificPermissionGrant).
        if ($subsidiary) {
            $subsidiaryPermissions = [
                'sellers.view', 'sellers.create', 'sellers.edit', 'sellers.delete',
                'subsidiaries.manage-own',
                'commissions.view-own', // "Mis Comisiones"
                'exchange.view-network', // Widget de tasa en el tablero
                'customers.view', 'customers.create', 'customers.edit',
                'games.bingo', // Acceso a juegos de bingo (reportes de su red)
                'games.bingo.winners', 'games.bingo.sales_report', // Ganadores y reporte de ventas de bingo
                'reports.view', 'reports.generate', 'reports.gaming',
                // Opciones independientes
                'directory.view', 'directory.search',
                'prizes.view', 'prizes.create', 'prizes.edit',
                'external-players.view', 'external-players.create', 'external-players.manage', 'external-players.block',
                // Trivia - Solo ver partidas y leaderboard (no jugar - es un rol de gestión)
                'games.trivia.view',
                'games.trivia.games.view',
                'games.trivia.leaderboard.view',
            ];
            $subsidiary->givePermissionTo($subsidiaryPermissions);
            $this->command->info('✅ Permisos asignados a Subsidiary');
        }

        // Seller: Gestión de ventas
        if ($seller) {
            $sellerPermissions = [
                'sellers.manage-own',
                'commissions.view-own', // "Mis Comisiones"
                'exchange.view-network', // Widget de tasa en el tablero
                'games.bingo', // Acceso a juegos de bingo
                'games.bingo.game_rooms', // Ver/comprar en salas de bingo — antes ausente: el ítem "Salas de Bingo" del sidebar solo lo tenía player, pese a que BingoCardGenerateService trata role==='seller' como caso de primera clase para comprar cartones (mismo flujo que commerce/subsidiary)
                // Cartones / Ganadores / Reporte de Ventas — BingoReportController
                // limita al seller a SUS ventas (seller_user_id), ver isSellerView().
                'games.bingo.sales_report', 'games.bingo.winners',
                'reports.view', 'reports.gaming',
                'external-players.view', 'external-players.create',
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
                // exchange.manage (no exchange.update, permiso huérfano que
                // ningún middleware consulta): ExchangeRateCrudService trata
                // a treasurer igual que admin/superadmin, gestionando la
                // tasa OFICIAL — sin este permiso, la separación view/manage
                // de routes/modules/exchange-rate.php lo dejaba solo con
                // lectura pese a que el propio dominio lo autoriza a editar.
                'exchange.view', 'exchange.manage', 'exchange.view-network',
                'reports.view', 'reports.generate', 'reports.export', 'reports.financial',
                // Opciones independientes
                'directory.view', 'directory.search',
                'prizes.view',
                // Trivia - Ver estadísticas y leaderboard (rol financiero)
                'games.trivia.view',
                'games.trivia.leaderboard.view',
                'games.trivia.stats.view', 'games.trivia.stats.export',
                'treasurers.manage-own', // Ficha propia "Mi Tesorería"
                // Cola de recargas/retiros: ver y aprobar/rechazar (rol
                // financiero; WalletMovementPolicy también lo admite).
                'wallet.movements.view', 'wallet.movements.manage',
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
                'games.bingo.manage', // Gestionar salas de bingo — customer tiene vendedores propios (ver sellers.view), la Policy (BingoBasePolicy::requiredRole) ya lo contemplaba para esto
                'wallet.view', // Ver su propia billetera (juega bingo con saldo propio)
                // Sin sellers.view: un customer no puede tener vendedores (ver
                // ManagerSellerPolicy::requiredRole()).
                'customers.manage-own', // Ficha propia "Mi Cliente"
                'commissions.view-own', // "Mis Comisiones": comisión de sala como creador
                'exchange.view-network', // Widget de tasa en el tablero
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
                'inventory.view', 'marketplace.listings.view', 'marketplace.listings.create',
                'marketplace.orders.create', 'marketplace.orders.view', // Marketplace P2P
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

        // External Player: rol LÓGICO sin User/login (ver RoleSeeder). Solo gobierna
        // qué juegos están habilitados para App\Models\Roles\Player\External\ExternalPlayer;
        // no tiene wallet propia (juega con saldo gestionado por el vendedor que lo captó).
        if ($externalPlayer) {
            $externalPlayerPermissions = [
                'games.bingo', // Acceso a juegos de bingo
                'games.trivia.view',
                'games.trivia.play',
            ];
            $externalPlayer->givePermissionTo($externalPlayerPermissions);
            $this->command->info('✅ Permisos asignados a External Player');
        }

        $this->command->info('✅ Asignación de permisos a roles completada');
    }
}
