<?php

namespace Database\Seeders;

use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
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
            ['name' => 'game.bingo', 'guard_name' => 'web', 'description' => 'Acceso a juegos de bingo'],
            ['name' => 'game.bingo.game_rooms', 'guard_name' => 'web', 'description' => 'Acceso a salas de bingo'],
            ['name' => 'game.bingo.cards_sold', 'guard_name' => 'web', 'description' => 'Acceso a cartones vendidos'],
            
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

            // Auditoría
            ['name' => 'audit.view', 'guard_name' => 'web', 'description' => 'Ver auditoría'],
            ['name' => 'audit.export', 'guard_name' => 'web', 'description' => 'Exportar auditoría'],

            // Opciones independientes del menú
            ['name' => 'directory.view', 'guard_name' => 'web', 'description' => 'Ver directorio de comercios'],
            ['name' => 'directory.search', 'guard_name' => 'web', 'description' => 'Buscar en directorio'],
            
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
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        }

        $this->command->info('✅ Permisos creados exitosamente');
    }
}
