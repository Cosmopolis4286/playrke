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
        // Llamar a todos los seeders necesarios (orden simplificado)
        $this->call([
            CountrySeeder::class,
            AmericanTimeZoneSeeder::class,
            CurrencySeeder::class,
            MovementTypeSeeder::class,
            BankVenezuelaSeeder::class,
            DocumentTypeSeeder::class,
            RoomTypeSeeder::class,
            GameSeeder::class,
            GameModeSeeder::class,
            ConnectionTypeSeeder::class,
            GameStatusSeeder::class,
            GamePrizeSeeder::class,
            GameConfigSeeder::class,
            PrizePoolTypeSeeder::class,
            JackpotTypeSeeder::class,
            BingoPatternSeeder::class,
            AppParameterSeeder::class,
            ModuleSeeder::class,
            TosVersionSeeder::class,
            WalletLimitSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            ScheduledCommandSeeder::class,
            SidebarMenuVisibilitySeeder::class,
            LandingSectionSeeder::class,
            TestUserSeeder::class,
            DemoRoleDataSeeder::class,
            ExternalPlayerSeeder::class,
        ]);
    }
}
