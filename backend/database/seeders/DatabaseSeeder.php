<?php

namespace Database\Seeders;

use App\Models\Game;
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
            CountrySeeder::class,
            AmericanTimeZoneSeeder::class,
            CurrencySeeder::class,
            BankVenezuelaSeeder::class,
            DocumentTypeSeeder::class,
            GameSeeder::class,
            GameModeSeeder::class,
            GameStatusSeeder::class,
            GamePrizeSeeder::class,
            GameConfigSeeder::class,
            PrizePoolTypeSeeder::class,
            GamePrizePoolTypeSeeder::class,
            BingoPatternSeeder::class,
            SettingSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
        ]);
    }
}
