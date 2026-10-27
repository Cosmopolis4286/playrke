<?php

namespace Database\Seeders;

use App\Models\Country;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run() : void
    {
        $countries = [
            ['name' => 'Brasil',    'iso_code_2' => 'BR', 'iso_code_3' => 'BRA', 'status' => 'ACTIVE'],
            ['name' => 'Colombia',  'iso_code_2' => 'CO', 'iso_code_3' => 'COL', 'status' => 'ACTIVE'],
            ['name' => 'Venezuela', 'iso_code_2' => 'VE', 'iso_code_3' => 'VEN', 'status' => 'ACTIVE'],
        ];

        $snowflake = new Snowflake;
        $now = now();

        // Preparar registros con ID y timestamps
        $payload = array_map(function($country) use ($snowflake, $now) {
            return [
                'id' => $snowflake->id(),
                'name' => $country['name'],
                'iso_code_2' => $country['iso_code_2'],
                'iso_code_3' => $country['iso_code_3'],
                'status' => $country['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $countries);

        // upsert evita duplicados y actualiza si existe
        Country::upsert(
            $payload,
            ['iso_code_2'],                       // columna única
            ['name', 'iso_code_3', 'status', 'updated_at'],
        );
    }
}
