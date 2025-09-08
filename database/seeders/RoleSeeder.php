<?php

namespace Database\Seeders;

use App\Models\User\Roles\Role;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = now();
        $snowflake = new Snowflake();

        $roles = [
            ['name' => 'Jugador',   'prefix' => 'player',     'description' => 'Jugador/Usuario del sistema', 'status' => 'ACTIVE'],
            ['name' => 'Admin',     'prefix' => 'admin',      'description' => 'Administrador',               'status' => 'ACTIVE'],
            ['name' => 'Comercio',  'prefix' => 'commerce',   'description' => 'Comercio',                    'status' => 'ACTIVE'],
            ['name' => 'Sucursal',  'prefix' => 'subsidiary', 'description' => 'Sucursal de un comercio',     'status' => 'ACTIVE'],
            ['name' => 'Vendedor',  'prefix' => 'seller',     'description' => 'Vendedor asociado',           'status' => 'ACTIVE'],
            ['name' => 'Proveedor', 'prefix' => 'provider',   'description' => 'Proveedor del sistema',       'status' => 'ACTIVE'],
            ['name' => 'Cliente',   'prefix' => 'customer',   'description' => 'Cliente final',               'status' => 'ACTIVE'],
            ['name' => 'Tesorero',  'prefix' => 'treasurer',  'description' => 'Responsable financiero',      'status' => 'ACTIVE'],
        ];

        foreach ($roles as &$role) {
            $role['id'] = $snowflake->id();   // ID único tipo Snowflake
            $role['created_at'] = $timestamp;
            $role['updated_at'] = $timestamp;
        }

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['prefix' => $role['prefix']], // clave única
                $role                          // incluye id, descripción, status y timestamps
            );
        }
    }
}
