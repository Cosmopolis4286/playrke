<?php

namespace Database\Seeders;

use App\Models\Role;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run() : void
    {
        $timestamp = now();
        $snowflake = new Snowflake;

        // Definimos todos los roles del sistema
        // 'type' indica si es rol web o API
        // 'sort_order' fija el nivel/orden en el organigrama (menor = más alto
        // en jerarquía) — editable luego desde /admin/permissions, ver
        // RoleController::update() y RoleHierarchyService.
        // 'description' usa el mismo texto que antes vivía hardcoded en
        // RoleHierarchyService::FUNCTIONS — description es la ÚNICA fuente de
        // verdad ahora (editable desde /admin/permissions vía RoleManagerModal
        // y RoleHierarchyTree, ambos escriben la misma columna), así que el
        // valor inicial ya nace con el texto rico que el organigrama mostraba.
        $roles = [
            ['name' => 'SuperAdmin', 'prefix' => 'superadmin', 'description' => 'Control total del sistema: roles, permisos, configuración global y respaldo.', 'status' => true, 'type' => 'web', 'sort_order' => 10, 'icon' => 'fa-crown', 'color' => '#7c3aed'],
            ['name' => 'Admin',     'prefix' => 'admin',      'description' => 'Gestión operativa transversal: usuarios, comercios, juegos, reportes y auditoría.', 'status' => true, 'type' => 'web', 'sort_order' => 20, 'icon' => 'fa-shield-alt', 'color' => '#2563eb'],
            ['name' => 'Comercio',  'prefix' => 'commerce',   'description' => 'Comercializadora: administra su negocio, agencias, vendedores y ventas de juegos.', 'status' => true, 'type' => 'web', 'sort_order' => 30, 'icon' => 'fa-store', 'color' => '#16a34a'],
            ['name' => 'Sucursal',  'prefix' => 'subsidiary', 'description' => 'Agencia/sucursal de una comercializadora — gestiona sus propias taquillas (vendedores).', 'status' => true, 'type' => 'web', 'sort_order' => 40, 'icon' => 'fa-code-branch', 'color' => '#0d9488'],
            ['name' => 'Vendedor',  'prefix' => 'seller',     'description' => 'Taquilla: vende cartones/participaciones y capta jugadores externos vía link.', 'status' => true, 'type' => 'web', 'sort_order' => 50, 'icon' => 'fa-tags', 'color' => '#ca8a04'],
            ['name' => 'Tesorero',  'prefix' => 'treasurer',  'description' => 'Responsable financiero: tesorería, tasas de cambio y reportes financieros.', 'status' => true, 'type' => 'web', 'sort_order' => 60, 'icon' => 'fa-dollar-sign', 'color' => '#0891b2'],
            ['name' => 'Cliente',   'prefix' => 'customer',   'description' => 'Cliente que usa los servicios de la plataforma con cuenta propia.',               'status' => true, 'type' => 'web', 'sort_order' => 70, 'icon' => 'fa-headset', 'color' => '#db2777'],      // Cambiado a 'web' para acceso al menú
            ['name' => 'Jugador',   'prefix' => 'player',     'description' => 'Jugador registrado: participa en salas de Bingo/Trivia con su propia billetera.', 'status' => true, 'type' => 'web', 'sort_order' => 80, 'icon' => 'fa-gamepad', 'color' => '#64748b'],
            // 'provider' es un rol RESERVADO: pensado para una futura integración de
            // proveedor externo de juegos vía API. Hoy no tiene tabla, modelo Eloquent,
            // controller ni gate propios — solo existe como fila en `roles` y como label
            // decorativo en SidebarMenuService. No eliminar sin antes construir su CRUD.
            ['name' => 'Proveedor', 'prefix' => 'provider',   'description' => 'Rol reservado para integración futura de proveedor externo de juegos vía API.', 'status' => true, 'type' => 'api', 'sort_order' => 90, 'icon' => 'fa-plug', 'color' => '#ea580c'],      // API guard
            // 'external_player' es un rol LÓGICO (sin User/login): gobierna permisos
            // y juegos habilitados para App\Models\Roles\Player\External\ExternalPlayer
            // (comprador de cartones sin cuenta, captado por un vendedor vía link).
            // No aparece en /admin/users ni en TestUserSeeder/DemoRoleDataSeeder.
            ['name' => 'Jugador Externo', 'prefix' => 'external_player', 'description' => 'Jugador SIN cuenta, captado por una taquilla vía link único, sin login.', 'status' => true, 'type' => 'web', 'sort_order' => 100, 'icon' => 'fa-user-clock', 'color' => '#9333ea'],
        ];

        // Añadimos ID único (Snowflake) y timestamps
        foreach ($roles as &$role) {
            $role['id'] = $snowflake->id();
            $role['created_at'] = $timestamp;
            $role['updated_at'] = $timestamp;

            // ------------------------------
            // Asignación automática del guard
            // - Roles web → 'web'
            // - Roles API → 'api'
            // ------------------------------
            $role['guard_name'] = $role['type'] === 'api' ? 'api' : 'web';
        }
        unset($role); // evita que la referencia del foreach anterior corrompa el último elemento al reusar $role abajo

        // Creamos o actualizamos cada rol en la base de datos.
        // Usa App\Models\Role (no Spatie\Permission\Models\Role): mismo
        // registro/tabla, pero App\Models\Role es el modelo real que consume
        // el resto del sistema (User::roles(), RoleUserRepository, etc.) y
        // tiene el cast id => string vía SnowflakeId.
        foreach ($roles as $role) {
            $existingRole = Role::where('name', $role['prefix'])
                ->where('guard_name', $role['guard_name'])
                ->first();

            if (! $existingRole) {
                $newRole = new Role;
                $newRole->id = (string) $snowflake->id();
                $newRole->name = $role['prefix'];           // player, admin, etc
                $newRole->guard_name = $role['guard_name']; // web o api
                $newRole->prefix = $role['prefix'];
                $newRole->type = $role['type'];
                $newRole->description = $role['description'];
                $newRole->status = $role['status'];
                $newRole->sort_order = $role['sort_order'];
                $newRole->icon = $role['icon'];
                $newRole->color = $role['color'];
                $newRole->save();
            }
        }

        $this->command->info('✅ Roles creados exitosamente');
    }
}
