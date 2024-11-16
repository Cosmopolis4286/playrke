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
        // Insertar los datos en la tabla 'coins'
        DB::table('coins')->insert([
            ['id' => 1, 'iso_code' => NULL, 'symbol' => 'USDT', 'short_name' => 'USDT', 'long_name' => 'Tether USDT', 'country' => NULL, 'status' => 1],
            ['id' => 2, 'iso_code' => 'VES', 'symbol' => 'Bs', 'short_name' => 'Bolívar', 'long_name' => 'Bolívar', 'country' => 234, 'status' => 1],
            ['id' => 3, 'iso_code' => 'BRL', 'symbol' => 'R$', 'short_name' => 'Real', 'long_name' => 'Real brasileño', 'country' => 29, 'status' => 1],
            ['id' => 4, 'iso_code' => 'COP', 'symbol' => '$', 'short_name' => 'Peso', 'long_name' => 'Peso colombiano', 'country' => 47, 'status' => 1]
        ]);
    }
}
