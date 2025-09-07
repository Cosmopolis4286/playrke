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
            ['name' => 'Brasil',    'iso_code_2' => 'BR', 'iso_code_3' => 'BRA', 'status' => true],
            ['name' => 'Colombia',  'iso_code_2' => 'CO', 'iso_code_3' => 'COL', 'status' => true],
            ['name' => 'Venezuela', 'iso_code_2' => 'VE', 'iso_code_3' => 'VEN', 'status' => true],
        ];

        Country::upsert(
            $countries,
            ['iso_code_2'], // clave única
            ['name', 'iso_code_3', 'status']
        );
    }
}
