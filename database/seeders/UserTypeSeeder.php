<?php

namespace Database\Seeders;

use App\Models\UserType;
use Illuminate\Database\Seeder;

/**
 * Seeder para poblar la tabla user_type con los roles base del sistema.
 *
 * Contiene los tipos de usuario predefinidos y asegura que no se creen duplicados.
 */
class UserTypeSeeder extends Seeder
{
    /**
     * Lista de tipos de usuario predefinidos.
     *
     * @var array<int,array<string,mixed>>
     */
    protected array $userTypes = [
        ['id' => 1, 'description' => 'Jugador',    'prefix' => 'user',       'status' => 1],
        ['id' => 2, 'description' => 'Admin',      'prefix' => 'admin',      'status' => 1],
        ['id' => 3, 'description' => 'Comercio',   'prefix' => 'commerce',   'status' => 1],
        ['id' => 4, 'description' => 'Sucursal',   'prefix' => 'subsidiary', 'status' => 1],
        ['id' => 5, 'description' => 'Vendedor',   'prefix' => 'seller',     'status' => 1],
        ['id' => 6, 'description' => 'Proveedor',  'prefix' => 'provider',   'status' => 1],
        ['id' => 7, 'description' => 'Cliente',    'prefix' => 'customer',   'status' => 1],
        ['id' => 8, 'description' => 'Tesorero',   'prefix' => 'treasurer',  'status' => 1],
    ];

    /**
     * Ejecutar el seeder.
     *
     * @return void
     */
    public function run(): void
    {
        // Inserta o actualiza los registros según el campo 'id'
        UserType::upsert(
            $this->userTypes,
            ['id'], // Campo único para evitar duplicados
            ['description', 'prefix', 'status'] // Campos que se actualizarán en caso de conflicto
        );
    }
}
