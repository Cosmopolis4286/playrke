<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['id' => 1, 'description' => 'Jugador', 'prefix' => 'user', 'status' => 1],
            ['id' => 2, 'description' => 'Admin', 'prefix' => 'admin', 'status' => 1],
            ['id' => 3, 'description' => 'Comercio', 'prefix' => 'commerce', 'status' => 1],
            ['id' => 4, 'description' => 'Vendedor', 'prefix' => 'seller', 'status' => 1],
            ['id' => 5, 'description' => 'Proveedor', 'prefix' => 'provider', 'status' => 1]
        ];

        // Insertar los datos en la tabla 'user_type'
        DB::table('user_type')->insert($data);
    }
}
