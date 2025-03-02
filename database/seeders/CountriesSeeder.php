<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertar los datos en la tabla 'countries'
        DB::table('countries')->insert([
            ['id' => 1, 'name' => 'Brasil', 'iso_code_2' => 'BR', 'iso_code_3' => 'BRA', 'status' => 1],
            ['id' => 2, 'name' => 'Colombia', 'iso_code_2' => 'CO', 'iso_code_3' => 'COL', 'status' => 1],
            ['id' => 3, 'name' => 'Venezuela', 'iso_code_2' => 'VE', 'iso_code_3' => 'VEN', 'status' => 1]
        ]);
    }
}
