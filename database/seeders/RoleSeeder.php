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
        ['name' => 'Jugador',    'prefix' => 'player',     'description' => 'Jugador/Usuario del sistema',    'status' => 1],
        ['name' => 'Admin',      'prefix' => 'admin',      'description' => 'Administrador',         'status' => 1],
        ['name' => 'Comercio',   'prefix' => 'commerce_id',   'description' => 'Comercio',              'status' => 1],
        ['name' => 'Sucursal',   'prefix' => 'subsidiary', 'description' => 'Sucursal de un comercio', 'status' => 1],
        ['name' => 'Vendedor',   'prefix' => 'seller',     'description' => 'Vendedor asociado',     'status' => 1],
        ['name' => 'Proveedor',  'prefix' => 'provider',   'description' => 'Proveedor del sistema', 'status' => 1],
        ['name' => 'Cliente',    'prefix' => 'customer',   'description' => 'Cliente final',         'status' => 1],
        ['name' => 'Tesorero',   'prefix' => 'treasurer',  'description' => 'Responsable financiero', 'status' => 1],
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
