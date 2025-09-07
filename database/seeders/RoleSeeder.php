<?php

namespace Database\Seeders;

use App\Models\User\Roles\Role;
use Illuminate\Database\Seeder;

/**
 * Seeder para poblar la tabla roles con los roles base del sistema.
 *
 * Contiene los roles predefinidos y asegura que no se creen duplicados.
 */
class RoleSeeder extends Seeder
{
    /**
     * Lista de roles predefinidos.
     *
     * @var array<int,array<string,mixed>>
     */
    protected array $roles = [
        ['name' => 'Jugador',    'prefix' => 'player',     'description' => 'Jugador/Usuario del sistema',    'status' => 'ACTIVE'],
        ['name' => 'Admin',      'prefix' => 'admin',      'description' => 'Administrador',         'status' => 'ACTIVE'],
        ['name' => 'Comercio',   'prefix' => 'commerce_id',   'description' => 'Comercio',              'status' => 'ACTIVE'],
        ['name' => 'Sucursal',   'prefix' => 'subsidiary', 'description' => 'Sucursal de un comercio', 'status' => 'ACTIVE'],
        ['name' => 'Vendedor',   'prefix' => 'seller',     'description' => 'Vendedor asociado',     'status' => 'ACTIVE'],
        ['name' => 'Proveedor',  'prefix' => 'provider',   'description' => 'Proveedor del sistema', 'status' => 'ACTIVE'],
        ['name' => 'Cliente',    'prefix' => 'customer',   'description' => 'Cliente final',         'status' => 'ACTIVE'],
        ['name' => 'Tesorero',   'prefix' => 'treasurer',  'description' => 'Responsable financiero', 'status' => 'ACTIVE'],
    ];

    /**
     * Ejecutar el seeder.
     *
     * @return void
     */
    public function run(): void
    {
        foreach ($this->roles as $roleData) {
            Role::updateOrCreate(
                ['prefix' => $roleData['prefix']], // clave única para evitar duplicados
                $roleData
            );
        }
    }
}
