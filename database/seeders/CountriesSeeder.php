<?php

namespace Database\Seeders;

use App\Models\Countries\Country;
use Illuminate\Database\Seeder;
use BradieTilley\Snowflake\Snowflake; // Importar Snowflake

class CountriesSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name' => 'Brasil',    'iso_code_2' => 'BR', 'iso_code_3' => 'BRA', 'status' => 'ACTIVE'],
            ['name' => 'Colombia',  'iso_code_2' => 'CO', 'iso_code_3' => 'COL', 'status' => 'ACTIVE'],
            ['name' => 'Venezuela', 'iso_code_2' => 'VE', 'iso_code_3' => 'VEN', 'status' => 'ACTIVE'],
        ];

        // Instanciar Snowflake
        $snowflake = new Snowflake();

        // Agregar ID Snowflake a cada registro
        $countries = array_map(function ($country) use ($snowflake) {
            $country['id'] = $snowflake->id(); // Usar ->id() en vez de generate()
            $country['created_at'] = now();
            $country['updated_at'] = now();
            return $country;
        }, $countries);

        // Inserción usando upsert para no duplicar
        Country::upsert(
            $countries,
            ['iso_code_2'], // clave única
            ['name', 'iso_code_3', 'status', 'updated_at'] // campos que se actualizan si ya existe
        );
    }
}
