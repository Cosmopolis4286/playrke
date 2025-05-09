<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use DateTimeZone;
use DateTime;

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
            // Obtener la diferencia en GMT
            $dateTimeZone = new DateTimeZone($timezone);
            $dateTime = new DateTime('now', $dateTimeZone);
            $offset = $dateTime->getOffset(); // Obtiene el offset en segundos

            // Convertir el offset a un formato GMT
            $gmt = 'GMT' . ($offset < 0 ? '-' : '+') . str_pad(abs($offset) / 3600, 2, '0', STR_PAD_LEFT);

            return [
                'name' => $timezone,
                'gmt' => $gmt,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }, $timezones);

        // Insertar en la base de datos
        DB::table('time_zones')->insert($data);
    }
}
