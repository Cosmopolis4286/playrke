<?php

namespace Database\Seeders;

use App\Models\Users\Role;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role as SpatieRole;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = now();
        $snowflake = new Snowflake();

        // Definimos todos los roles del sistema
        // 'type' indica si es rol web o API
        $roles = [
            ['name' => 'Jugador',   'prefix' => 'player',     'description' => 'Jugador/Usuario del sistema', 'status' => true, 'type' => 'web'],
            ['name' => 'Admin',     'prefix' => 'admin',      'description' => 'Administrador',               'status' => true, 'type' => 'web'],
            ['name' => 'Comercio',  'prefix' => 'commerce',   'description' => 'Comercio',                    'status' => true, 'type' => 'web'],
            ['name' => 'Sucursal',  'prefix' => 'subsidiary', 'description' => 'Sucursal de un comercio',     'status' => true, 'type' => 'web'],
            ['name' => 'Vendedor',  'prefix' => 'seller',     'description' => 'Vendedor asociado',           'status' => true, 'type' => 'web'],
            ['name' => 'Proveedor', 'prefix' => 'provider',   'description' => 'Proveedor del sistema',       'status' => true, 'type' => 'api'],      // API guard
            ['name' => 'Cliente',   'prefix' => 'customer',   'description' => 'Cliente final',               'status' => true, 'type' => 'api'],      // API guard
            ['name' => 'Tesorero',  'prefix' => 'treasurer',  'description' => 'Responsable financiero',      'status' => true, 'type' => 'web'],
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

        // Creamos o actualizamos cada rol en la base de datos
        // La tabla 'roles' incluye todos los campos (Spatie + custom)
        foreach ($roles as $role) {
            $existingRole = SpatieRole::where('name', $role['prefix'])
                ->where('guard_name', $role['guard_name'])
                ->first();

            if (!$existingRole) {
                // Crear nuevo rol con Snowflake ID
                $spatieRole = new SpatieRole();
                $spatieRole->id = $snowflake->id();
                $spatieRole->name = $role['prefix'];           // player, admin, etc
                $spatieRole->guard_name = $role['guard_name']; // web o api
                $spatieRole->prefix = $role['prefix'];
                $spatieRole->type = $role['type'];
                $spatieRole->description = $role['description'];
                $spatieRole->status = $role['status'];
                $spatieRole->save();
            }
        }

        $this->command->info('✅ Roles creados exitosamente');
    }
}
