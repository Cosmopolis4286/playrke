<?php

namespace Database\Seeders;

use App\Models\Coins\Coin;
use App\Models\Countries\Country;

use Illuminate\Database\Seeder;

class CoinsSeeder extends Seeder
{
    /**
     * Ejecutar el seeder para poblar la tabla 'coins'.
     *
     * @return void
     */
    public function run(): void
    {
        // Obtener IDs de países por ISO, si existen
        $countries = Country::whereIn('iso_code_2', ['BR', 'CO', 'VE'])
            ->pluck('id', 'iso_code_2')
            ->toArray();

        $coins = [
            [
                'iso_code'   => 'USDT',
                'symbol'     => 'USDT',
                'short_name' => 'USDT',
                'long_name'  => 'Tether USDT',
                'country'    => null,
                'status'     => 1
            ],
            [
                'iso_code'   => 'BRL',
                'symbol'     => 'R$',
                'short_name' => 'Real',
                'long_name'  => 'Real brasileño',
                'country'    => $countries['BR'] ?? null,
                'status'     => 1
            ],
            [
                'iso_code'   => 'COP',
                'symbol'     => '$',
                'short_name' => 'Peso',
                'long_name'  => 'Peso colombiano',
                'country'    => $countries['CO'] ?? null,
                'status'     => 1
            ],
            [
                'iso_code'   => 'VES',
                'symbol'     => 'Bs',
                'short_name' => 'Bolívar',
                'long_name'  => 'Bolívar venezolano',
                'country'    => $countries['VE'] ?? null,
                'status'     => 1
            ]
        ];

        // Insertar o actualizar sin duplicar
        foreach ($coins as $coin) {
            Coin::updateOrInsert(
                ['iso_code' => $coin['iso_code']],
                $coin
            );
        }
    }
}
