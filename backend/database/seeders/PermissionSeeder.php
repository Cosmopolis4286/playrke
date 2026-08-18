<?php

namespace Database\Seeders;

use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Permisos que gobiernan el propio sistema de roles/permisos y la
     * configuración global — si se editan o eliminan por error, pueden dejar
     * sin acceso administrativo a toda la plataforma (ver PermissionPolicy).
     */
    private const SYSTEM_PERMISSIONS = [
        'superadmin.access',
        'roles.view',
        'roles.create',
        'roles.edit',
        'roles.delete',
        'roles.assign',
        'system.settings',
        'system.maintenance',
        'system.logs',
        'system.backup',
        'system.jobs',
    ];

    public function run(): void
    {
        $timestamp = now();
        $snowflake = new Snowflake();

        // Permisos generales del sistema
        $permissions = [
            // Gestión de usuarios
            ['name' => 'users.view', 'guard_name' => 'web', 'description' => 'Ver usuarios'],
            ['name' => 'users.create', 'guard_name' => 'web', 'description' => 'Crear usuarios'],
            ['name' => 'users.edit', 'guard_name' => 'web', 'description' => 'Editar usuarios'],
            ['name' => 'users.delete', 'guard_name' => 'web', 'description' => 'Eliminar usuarios'],
            ['name' => 'users.manage', 'guard_name' => 'web', 'description' => 'Gestionar usuarios completos'],

            // Gestión de roles
            ['name' => 'roles.view', 'guard_name' => 'web', 'description' => 'Ver roles'],
            ['name' => 'roles.create', 'guard_name' => 'web', 'description' => 'Crear roles'],
            ['name' => 'roles.edit', 'guard_name' => 'web', 'description' => 'Editar roles'],
            ['name' => 'roles.delete', 'guard_name' => 'web', 'description' => 'Eliminar roles'],
            ['name' => 'roles.assign', 'guard_name' => 'web', 'description' => 'Asignar roles a usuarios'],

            // Gestión de comercios
            ['name' => 'commerces.view', 'guard_name' => 'web', 'description' => 'Ver comercios'],
            ['name' => 'commerces.create', 'guard_name' => 'web', 'description' => 'Crear comercios'],
            ['name' => 'commerces.edit', 'guard_name' => 'web', 'description' => 'Editar comercios'],
            ['name' => 'commerces.delete', 'guard_name' => 'web', 'description' => 'Eliminar comercios'],
            ['name' => 'commerces.approve', 'guard_name' => 'web', 'description' => 'Aprobar comercios'],

            // Gestión de vendedores
            ['name' => 'sellers.view', 'guard_name' => 'web', 'description' => 'Ver vendedores'],
            ['name' => 'sellers.create', 'guard_name' => 'web', 'description' => 'Crear vendedores'],
            ['name' => 'sellers.edit', 'guard_name' => 'web', 'description' => 'Editar vendedores'],
            ['name' => 'sellers.delete', 'guard_name' => 'web', 'description' => 'Eliminar vendedores'],

            // Gestión de clientes (API)
            ['name' => 'customers.view', 'guard_name' => 'web', 'description' => 'Ver clientes'],
            ['name' => 'customers.create', 'guard_name' => 'web', 'description' => 'Crear clientes'],
            ['name' => 'customers.edit', 'guard_name' => 'web', 'description' => 'Editar clientes'],
            ['name' => 'customers.delete', 'guard_name' => 'web', 'description' => 'Eliminar clientes'],
            ['name' => 'customers.api.access', 'guard_name' => 'api', 'description' => 'Acceso API de cliente'],

            // Gestión de tesorería
            ['name' => 'treasury.view', 'guard_name' => 'web', 'description' => 'Ver tesorería'],
            ['name' => 'treasury.manage', 'guard_name' => 'web', 'description' => 'Gestionar tesorería'],
            ['name' => 'treasury.approve.transactions', 'guard_name' => 'web', 'description' => 'Aprobar transacciones'],
            ['name' => 'treasury.reports', 'guard_name' => 'web', 'description' => 'Reportes de tesorería'],

            // Gestión de tasas de cambio
            ['name' => 'exchange.view', 'guard_name' => 'web', 'description' => 'Ver tasas de cambio'],
            ['name' => 'exchange.manage', 'guard_name' => 'web', 'description' => 'Gestionar tasas de cambio'],
            ['name' => 'exchange.update', 'guard_name' => 'web', 'description' => 'Actualizar tasas de cambio'],

            // Gestión de juegos
            ['name' => 'games.view', 'guard_name' => 'web', 'description' => 'Ver juegos'],
            ['name' => 'games.create', 'guard_name' => 'web', 'description' => 'Crear juegos'],
            ['name' => 'games.edit', 'guard_name' => 'web', 'description' => 'Editar juegos'],
            ['name' => 'games.delete', 'guard_name' => 'web', 'description' => 'Eliminar juegos'],
            ['name' => 'games.manage', 'guard_name' => 'web', 'description' => 'Gestionar juegos'],
            
            // Juegos específicos
            // Bingo - Per
            ['name' => 'games.bingo', 'guard_name' => 'web', 'description' => 'Acceso a juegos de bingo'],
            ['name' => 'games.bingo.game_rooms', 'guard_name' => 'web', 'description' => 'Acceso a salas de bingo'],
            ['name' => 'games.bingo.cards_sold', 'guard_name' => 'web', 'description' => 'Acceso a cartones vendidos'],
            ['name' => 'games.bingo.manage', 'guard_name' => 'web', 'description' => 'Gestionar juegos de bingo'],
            ['name' => 'games.bingo.winners', 'guard_name' => 'web', 'description' => 'Ver ganadores de bingo'],
            ['name' => 'games.bingo.sales_report', 'guard_name' => 'web', 'description' => 'Ver reporte de ventas de bingo'],
            
            // Trivia - Permisos generales
            ['name' => 'games.trivia.view', 'guard_name' => 'web', 'description' => 'Ver sección de trivia'],
            ['name' => 'games.trivia.play', 'guard_name' => 'web', 'description' => 'Jugar trivia (único para jugadores)'],

            // Trivia - Gestión de salas
            ['name' => 'games.trivia.rooms.view', 'guard_name' => 'web', 'description' => 'Ver salas de trivia'],
            ['name' => 'games.trivia.rooms.create', 'guard_name' => 'web', 'description' => 'Crear salas de trivia'],
            ['name' => 'games.trivia.rooms.edit', 'guard_name' => 'web', 'description' => 'Editar salas de trivia'],
            ['name' => 'games.trivia.rooms.delete', 'guard_name' => 'web', 'description' => 'Eliminar salas de trivia'],
            ['name' => 'games.trivia.rooms.manage', 'guard_name' => 'web', 'description' => 'Gestionar salas de trivia (CRUD completo)'],

            // Trivia - Gestión de preguntas
            ['name' => 'games.trivia.questions.view', 'guard_name' => 'web', 'description' => 'Ver banco de preguntas'],
            ['name' => 'games.trivia.questions.create', 'guard_name' => 'web', 'description' => 'Crear preguntas'],
            ['name' => 'games.trivia.questions.edit', 'guard_name' => 'web', 'description' => 'Editar preguntas'],
            ['name' => 'games.trivia.questions.delete', 'guard_name' => 'web', 'description' => 'Eliminar preguntas'],
            ['name' => 'games.trivia.questions.manage', 'guard_name' => 'web', 'description' => 'Gestionar banco de preguntas (CRUD completo)'],

            // Trivia - Gestión de partidas
            ['name' => 'games.trivia.games.view', 'guard_name' => 'web', 'description' => 'Ver partidas de trivia'],
            ['name' => 'games.trivia.games.manage', 'guard_name' => 'web', 'description' => 'Gestionar partidas (iniciar/detener)'],

            // Trivia - Clasificación y estadísticas
            ['name' => 'games.trivia.leaderboard.view', 'guard_name' => 'web', 'description' => 'Ver clasificación de trivia'],
            ['name' => 'games.trivia.stats.view', 'guard_name' => 'web', 'description' => 'Ver estadísticas de trivia'],
            ['name' => 'games.trivia.stats.export', 'guard_name' => 'web', 'description' => 'Exportar estadísticas de trivia'],

            // Reportes
            ['name' => 'reports.view', 'guard_name' => 'web', 'description' => 'Ver reportes'],
            ['name' => 'reports.generate', 'guard_name' => 'web', 'description' => 'Generar reportes'],
            ['name' => 'reports.export', 'guard_name' => 'web', 'description' => 'Exportar reportes'],
            ['name' => 'reports.financial', 'guard_name' => 'web', 'description' => 'Reportes financieros'],
            ['name' => 'reports.gaming', 'guard_name' => 'web', 'description' => 'Reportes de juegos'],

            // Configuración del sistema
            ['name' => 'system.settings', 'guard_name' => 'web', 'description' => 'Configuración del sistema'],
            ['name' => 'system.maintenance', 'guard_name' => 'web', 'description' => 'Modo mantenimiento'],
            ['name' => 'system.logs', 'guard_name' => 'web', 'description' => 'Ver logs del sistema'],
            ['name' => 'system.backup', 'guard_name' => 'web', 'description' => 'Gestionar backups'],
            ['name' => 'system.jobs', 'guard_name' => 'web', 'description' => 'Ver y gestionar jobs, colas y scheduler del sistema'],
            ['name' => 'settings.manage', 'guard_name' => 'web', 'description' => 'Gestionar configuración general de la plataforma'],

            // Auditoría
            ['name' => 'audit.view', 'guard_name' => 'web', 'description' => 'Ver auditoría'],
            ['name' => 'audit.export', 'guard_name' => 'web', 'description' => 'Exportar auditoría'],

            // KYC (verificación de identidad)
            ['name' => 'kyc.view', 'guard_name' => 'web', 'description' => 'Ver documentos KYC'],
            ['name' => 'kyc.manage', 'guard_name' => 'web', 'description' => 'Aprobar/rechazar documentos KYC'],

            // Eventos de ganancia (ticker de la plataforma)
            ['name' => 'win-events.manage', 'guard_name' => 'web', 'description' => 'Registrar y ver eventos de ganancia manuales'],

            // Opciones independientes del menú
            ['name' => 'directory.view', 'guard_name' => 'web', 'description' => 'Ver directorio de comercios'],
            ['name' => 'directory.search', 'guard_name' => 'web', 'description' => 'Buscar en directorio'],

            // Jugadores externos (compradores sin cuenta en la plataforma, vía link de vendedor)
            ['name' => 'external-players.view', 'guard_name' => 'web', 'description' => 'Ver jugadores externos'],
            ['name' => 'external-players.create', 'guard_name' => 'web', 'description' => 'Generar links de jugador externo'],
            ['name' => 'external-players.manage', 'guard_name' => 'web', 'description' => 'Gestionar jugadores externos'],
            ['name' => 'external-players.block', 'guard_name' => 'web', 'description' => 'Bloquear/desbloquear jugadores externos'],
            ['name' => 'external-players.export', 'guard_name' => 'web', 'description' => 'Exportar jugadores externos'],
            
            ['name' => 'prizes.view', 'guard_name' => 'web', 'description' => 'Ver premios'],
            ['name' => 'prizes.create', 'guard_name' => 'web', 'description' => 'Crear premios'],
            ['name' => 'prizes.edit', 'guard_name' => 'web', 'description' => 'Editar premios'],
            ['name' => 'prizes.delete', 'guard_name' => 'web', 'description' => 'Eliminar premios'],
            ['name' => 'prizes.assign', 'guard_name' => 'web', 'description' => 'Asignar premios'],

            // Billetera/Wallet
            ['name' => 'wallet.view', 'guard_name' => 'web', 'description' => 'Ver billetera'],
            ['name' => 'wallet.deposit', 'guard_name' => 'web', 'description' => 'Depositar en billetera'],
            ['name' => 'wallet.withdraw', 'guard_name' => 'web', 'description' => 'Retirar de billetera'],
            ['name' => 'wallet.movements', 'guard_name' => 'web', 'description' => 'Ver movimientos de billetera'],
            ['name' => 'wallet.movements.view', 'guard_name' => 'web', 'description' => 'Ver todos los movimientos de billetera (admin)'],
            ['name' => 'wallet.movements.manage', 'guard_name' => 'web', 'description' => 'Gestionar movimientos de billetera (admin)'],
            ['name' => 'movement-types.view', 'guard_name' => 'web', 'description' => 'Ver catálogo de tipos de movimiento'],
            ['name' => 'movement-types.manage', 'guard_name' => 'web', 'description' => 'Gestionar catálogo de tipos de movimiento (crear/editar/desactivar)'],
            ['name' => 'wallet-limits.view', 'guard_name' => 'web', 'description' => 'Ver límites de depósito/retiro de billetera'],
            ['name' => 'wallet-limits.manage', 'guard_name' => 'web', 'description' => 'Gestionar límites de depósito/retiro de billetera (crear/editar/desactivar)'],

            // Billetera de plataforma (caja interna del sistema)
            ['name' => 'platform.wallet.view', 'guard_name' => 'web', 'description' => 'Ver billetera de plataforma'],
            ['name' => 'platform.wallet.manage', 'guard_name' => 'web', 'description' => 'Depositar/retirar en la billetera de plataforma'],

            // Permisos de SuperAdmin (acceso total)
            ['name' => 'superadmin.access', 'guard_name' => 'web', 'description' => 'Acceso total al sistema'],
        ];

        // Crear permisos
        foreach ($permissions as $permission) {
            $existingPermission = Permission::where('name', $permission['name'])
                ->where('guard_name', $permission['guard_name'])
                ->first();

            if (!$existingPermission) {
                Permission::create([
                    'id' => $snowflake->id(),
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name'],
                    'description' => $permission['description'] ?? null,
                    'is_system' => in_array($permission['name'], self::SYSTEM_PERMISSIONS, true),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        }

        $this->command->info('✅ Permisos creados exitosamente');
    }
}
