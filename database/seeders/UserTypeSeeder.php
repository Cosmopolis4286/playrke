<?php

namespace Database\Seeders;

use App\Models\UserType;
use Illuminate\Database\Seeder;

class UserTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertar los datos en la tabla 'user_type'
        UserType::insert([
            ['id' => 1, 'description' => 'Jugador', 'prefix' => 'user', 'status' => 1],
            ['id' => 2, 'description' => 'Admin', 'prefix' => 'admin', 'status' => 1],
            ['id' => 3, 'description' => 'Comercio', 'prefix' => 'commerce', 'status' => 1],
            ['id' => 4, 'description' => 'Sucursal', 'prefix' => 'subsidiary', 'status' => 1],
            ['id' => 5, 'description' => 'Vendedor', 'prefix' => 'seller', 'status' => 1],
            ['id' => 6, 'description' => 'Proveedor', 'prefix' => 'provider', 'status' => 1],
            ['id' => 7, 'description' => 'Cliente', 'prefix' => 'customer', 'status' => 1],
            ['id' => 8, 'description' => 'Tesorero', 'prefix' => 'treasurer', 'status' => 1]
        ]);
    }
}
