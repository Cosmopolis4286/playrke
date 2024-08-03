<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AmericanTimeZonesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Obtener todas las zonas horarias que comienzan con 'America/'
        $timezones = array_filter(timezone_identifiers_list(), function ($timezone) {
            return strpos($timezone, 'America/') === 0;
        });

        // Preparar los datos para inserción
        $data = array_map(function ($timezone) {
            return ['name' => $timezone, 'created_at' => now(), 'updated_at' => now()];
        }, $timezones);

        // Insertar en la base de datos
        DB::table('time_zones')->insert($data);
    }
}
