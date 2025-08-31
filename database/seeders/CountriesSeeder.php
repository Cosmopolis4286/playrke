<?php

namespace Database\Seeders;

use App\Models\Countries\Country;
use Illuminate\Database\Seeder;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            ['name' => 'Brasil',    'iso_code_2' => 'BR', 'iso_code_3' => 'BRA', 'status' => 1],
            ['name' => 'Colombia',  'iso_code_2' => 'CO', 'iso_code_3' => 'COL', 'status' => 1],
            ['name' => 'Venezuela', 'iso_code_2' => 'VE', 'iso_code_3' => 'VEN', 'status' => 1],
        ];

        Country::upsert(
            $countries,
            ['iso_code_2'], // campo único para evitar duplicados
            ['name', 'iso_code_3', 'status'] // columnas que se actualizan si ya existe
        );
    }
}
