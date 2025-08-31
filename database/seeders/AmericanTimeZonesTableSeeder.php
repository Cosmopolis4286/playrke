<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use DateTimeZone;
use DateTime;

class AmericanTimeZonesTableSeeder extends Seeder
{
    public function run()
    {
        // Países y patrones de búsqueda en las zonas horarias de PHP
        $countriesPatterns = [
            'Venezuela' => ['America/Caracas'],
            'Colombia'  => ['America/Bogota'],
            'Chile'     => ['America/Santiago', 'Pacific/Easter'],
            'Brazil'    => ['America/'] // Brasil tiene múltiples zonas que comienzan con America/
        ];

        $allTimezones = timezone_identifiers_list();
        $allowedTimezones = [];

        foreach ($allTimezones as $tz) {
            foreach ($countriesPatterns as $country => $patterns) {
                foreach ($patterns as $pattern) {
                    // Incluye si contiene el patrón (exacto o como prefijo)
                    if ($pattern === $tz || str_starts_with($tz, $pattern)) {
                        $allowedTimezones[] = $tz;
                    }
                }
            }
        }

        // Eliminar duplicados
        $allowedTimezones = array_unique($allowedTimezones);

        // Preparar los datos para inserción
        $data = array_map(function ($timezone) {
            $dtz = new DateTimeZone($timezone);
            $dt = new DateTime('now', $dtz);
            $offset = $dt->getOffset();
            $utc = 'UTC' . ($offset < 0 ? '-' : '+') . str_pad(abs($offset) / 3600, 2, '0', STR_PAD_LEFT);

            return [
                'name' => $timezone,
                'utc' => $utc,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $allowedTimezones);

        // Insertar en la base de datos
        DB::table('time_zones')->insert($data);
    }
}
