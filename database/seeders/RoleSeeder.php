<?php

namespace Database\Seeders;

use App\Models\Users\Roles\Role;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = now();
        $snowflake = new Snowflake();

        // Definimos todos los roles del sistema
        // 'type' indica si es rol web o API
        $roles = [
            ['name' => 'Jugador',   'prefix' => 'player',     'description' => 'Jugador/Usuario del sistema', 'is_active' => true, 'type' => 'web'],
            ['name' => 'Admin',     'prefix' => 'admin',      'description' => 'Administrador',               'is_active' => true, 'type' => 'web'],
            ['name' => 'Comercio',  'prefix' => 'commerce',   'description' => 'Comercio',                    'is_active' => true, 'type' => 'web'],
            ['name' => 'Sucursal',  'prefix' => 'subsidiary', 'description' => 'Sucursal de un comercio',     'is_active' => true, 'type' => 'web'],
            ['name' => 'Vendedor',  'prefix' => 'seller',     'description' => 'Vendedor asociado',           'is_active' => true, 'type' => 'web'],
            ['name' => 'Proveedor', 'prefix' => 'provider',   'description' => 'Proveedor del sistema',       'is_active' => true, 'type' => 'api'],      // API guard
            ['name' => 'Cliente',   'prefix' => 'customer',   'description' => 'Cliente final',               'is_active' => true, 'type' => 'api'],      // API guard
            ['name' => 'Tesorero',  'prefix' => 'treasurer',  'description' => 'Responsable financiero',      'is_active' => true, 'type' => 'web'],
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
        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['prefix' => $role['prefix']], // clave única para no duplicar
                $role                          // incluye id, description, is_active, guard_name y timestamps
            );
        }
    }
}
