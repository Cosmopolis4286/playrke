<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Llamar a todos los seeders necesarios
        $this->call([
            AmericanTimeZonesTableSeeder::class,
            CountriesSeeder::class,
            CurrenciesSeeder::class,
            BanksVenezuelaSeeder::class,
            DocumentTypeSeeder::class,
            GamesSeeder::class,
            GameModesSeeder::class,
            GameStatusSeeder::class,
            GamePrizesSeeder::class,
            PrizePoolTypeSeeder::class,
            GamePrizePoolTypeSeeder::class,
            BingoPatternsSeeder::class,
            SettingsSeeder::class,
            UsersSeeder::class,
            RoleSeeder::class
        ]);
    }
}
