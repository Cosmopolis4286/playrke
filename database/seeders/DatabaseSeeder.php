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
            UserTypeSeeder::class,
            CountriesSeeder::class,
            CoinsSeeder::class,
            BanksVenSeeder::class,
            DocumentTypeSeeder::class,
            GamesSeeder::class,
            GameModesSeeder::class,
            GameStatusSeeder::class,
            GamePrizesSeeder::class,
            TypePrizePoolSeeder::class,
            GameTypePrizePoolSeeder::class,
            SettingsSeeder::class,
            UsersSeeder::class
        ]);
    }
}
