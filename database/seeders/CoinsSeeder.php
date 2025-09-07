<?php

namespace Database\Seeders;

use App\Models\Coins\Coin;
use App\Models\Countries\Country;

use Illuminate\Database\Seeder;

class CoinsSeeder extends Seeder
{
    /**
     * Ejecutar el seeder para poblar la tabla 'currencies'.
     *
     * @return void
     */
    public function run(): void
    {
        try {
            // Obtener IDs de países por ISO, si existen
            $countries = Country::whereIn('iso_code_2', ['BR', 'CO', 'VE'])
                ->pluck('id', 'iso_code_2')
                ->toArray();

            $currencies = [
                [
                    'iso_code'        => 'USDT',
                    'currency_symbol' => 'USDT',
                    'short_name'      => 'USDT',
                    'full_name'       => 'Tether USDT',
                    'country_id'      => null,
                    'is_active'       => true,
                    'is_crypto'       => true,
                    'decimal_places'  => 6,
                    'exchange_rate'   => 1.0000
                ],
                [
                    'iso_code'        => 'BRL',
                    'currency_symbol' => 'R$',
                    'short_name'      => 'Real',
                    'full_name'       => 'Real brasileño',
                    'country_id'      => $countries['BR'] ?? null,
                    'is_active'       => true,
                    'is_crypto'       => false,
                    'decimal_places'  => 2,
                    'exchange_rate'   => 5.2000
                ],
                [
                    'iso_code'        => 'COP',
                    'currency_symbol' => '$',
                    'short_name'      => 'Peso',
                    'full_name'       => 'Peso colombiano',
                    'country_id'      => $countries['CO'] ?? null,
                    'is_active'       => true,
                    'is_crypto'       => false,
                    'decimal_places'  => 2,
                    'exchange_rate'   => 4200.0000
                ],
                [
                    'iso_code'        => 'VES',
                    'currency_symbol' => 'Bs',
                    'short_name'      => 'Bolívar',
                    'full_name'       => 'Bolívar venezolano',
                    'country_id'      => $countries['VE'] ?? null,
                    'is_active'       => true,
                    'is_crypto'       => false,
                    'decimal_places'  => 2,
                    'exchange_rate'   => 36.5000
                ]
            ];

            // Insertar o actualizar sin duplicar con validación
            foreach ($currencies as $currency) {
                // Validar datos antes de insertar
                if (empty($currency['iso_code']) || empty($currency['currency_symbol'])) {
                    $this->command->warn("Saltando moneda con datos incompletos: " . json_encode($currency));
                    continue;
                }

                Coin::updateOrInsert(
                    ['iso_code' => $currency['iso_code']],
                    array_merge($currency, [
                        'created_at' => now(),
                        'updated_at' => now()
                    ])
                );

                $this->command->info("Moneda {$currency['iso_code']} procesada correctamente");
            }

            $this->command->info("Seeder de monedas completado exitosamente");

        } catch (\Exception $e) {
            $this->command->error("Error en CoinsSeeder: " . $e->getMessage());
            throw $e;
        }
    }
}
