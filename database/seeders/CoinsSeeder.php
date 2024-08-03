<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoinsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['id' => 1, 'symbol' => 'USDT', 'short_name' => 'USDT', 'long_name' => 'Tether USDT', 'country' => NULL, 'status' => 1],
            ['id' => 2, 'symbol' => 'Bs', 'short_name' => 'Bolívar', 'long_name' => 'Bolívar', 'country' => 234, 'status' => 1],
            ['id' => 3, 'symbol' => 'R$', 'short_name' => 'Real', 'long_name' => 'Real brasileño', 'country' => 29, 'status' => 1],
        ];

        // Insertar los datos en la tabla 'coins'
        DB::table('coins')->insert($data);
    }
}
