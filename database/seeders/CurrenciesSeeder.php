<?php

namespace Database\Seeders;

use App\Models\Currencies\Currency;
use App\Models\Countries\Country;
use Illuminate\Database\Seeder;

class CurrenciesSeeder extends Seeder
{
    /**
     * Ejecutar el seeder para poblar la tabla 'currencies'.
     */
    public function run(): void
    {
        try {
            // Obtener IDs de países por ISO (si existen)
            $countries = Country::whereIn('iso_code_2', ['BR', 'CO', 'VE'])
                ->pluck('id', 'iso_code_2')
                ->toArray();

            // Definición de monedas base
            $currencies = [
                [
                    'code'           => 'USDT',
                    'symbol'         => 'USDT',
                    'name'           => 'USDT',
                    'description'    => 'Tether USDT',
                    'country_id'     => null,
                    'type'           => 'CRYPTO',
                    'decimal_places' => 2,
                    'is_active'      => true,
                ],
                [
                    'code'           => 'BRL',
                    'symbol'         => 'R$',
                    'name'           => 'Real',
                    'description'    => 'Real brasileño',
                    'country_id'     => $countries['BR'] ?? null,
                    'type'           => 'FIAT',
                    'decimal_places' => 2,
                    'is_active'      => true,
                ],
                [
                    'code'           => 'COP',
                    'symbol'         => '$',
                    'name'           => 'Peso',
                    'description'    => 'Peso colombiano',
                    'country_id'     => $countries['CO'] ?? null,
                    'type'           => 'FIAT',
                    'decimal_places' => 2,
                    'is_active'      => true,
                ],
                [
                    'code'           => 'VES',
                    'symbol'         => 'Bs',
                    'name'           => 'Bolívar',
                    'description'    => 'Bolívar venezolano',
                    'country_id'     => $countries['VE'] ?? null,
                    'type'           => 'FIAT',
                    'decimal_places' => 2,
                    'is_active'      => true,
                ],
            ];

            // Inserción o actualización en lote
            Currency::upsert(
                $currencies,
                ['code'], // clave única
                ['symbol', 'name', 'description', 'country_id', 'type', 'decimal_places', 'is_active', 'updated_at']
            );

            $this->command->info("Seeder de currencies completado exitosamente");
        } catch (\Exception $e) {
            $this->command->error("Error en CurrenciesSeeder: " . $e->getMessage());
            throw $e;
        }
    }
}
