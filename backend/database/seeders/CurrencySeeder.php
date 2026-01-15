<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Country;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $countries = Country::whereIn('iso_code_2', ['BR', 'CO', 'VE'])
            ->pluck('id', 'iso_code_2')
            ->toArray();

        $currencies = [
            ['iso_code' => 'USDT', 'symbol' => 'USDT', 'name' => 'USDT', 'description' => 'Tether USDT', 'country_id' => null, 'type' => 'CRYPTO', 'decimal_places' => 2, 'status' => true],
            ['iso_code' => 'BRL', 'symbol' => 'R$', 'name' => 'Real', 'description' => 'Real brasileño', 'country_id' => $countries['BR'] ?? null, 'type' => 'FIAT', 'decimal_places' => 2, 'status' => true],
            ['iso_code' => 'COP', 'symbol' => '$', 'name' => 'Peso', 'description' => 'Peso colombiano', 'country_id' => $countries['CO'] ?? null, 'type' => 'FIAT', 'decimal_places' => 2, 'status' => true],
            ['iso_code' => 'VES', 'symbol' => 'Bs', 'name' => 'Bolívar', 'description' => 'Bolívar venezolano', 'country_id' => $countries['VE'] ?? null, 'type' => 'FIAT', 'decimal_places' => 2, 'status' => true],
        ];

        $snowflake = new Snowflake();

        $currencies = array_map(function ($currency) use ($snowflake) {
            $currency['id'] = $snowflake->id(); // genera ID único
            $currency['created_at'] = now();
            $currency['updated_at'] = now();
            return $currency;
        }, $currencies);

        Currency::upsert(
            $currencies,
            ['iso_code'], // clave única
            ['symbol', 'name', 'description', 'country_id', 'type', 'decimal_places', 'status', 'updated_at']
        );
    }
}
