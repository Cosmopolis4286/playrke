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
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            ScheduledCommandSeeder::class,
            SidebarMenuVisibilitySeeder::class,
            LandingSectionSeeder::class,
            TestUserSeeder::class,
            DemoRoleDataSeeder::class,
            ExternalPlayerSeeder::class,
            // ExchangeRateSeeder necesita un User real (user_id NOT NULL en
            // exchange_rates) — debe correr después de TestUserSeeder. A su
            // vez, WalletLimitSeeder ahora usa WalletLimitService::recommend()
            // para los límites específicos por moneda, que depende de una
            // tasa ACTIVE por moneda (CurrencyExchangeRateService) — por eso
            // ambos se movieron al final del pipeline, después de vivir junto
            // a WalletLimitSeeder original (línea de arriba, antes de
            // RoleSeeder) cuando solo sembraba el fallback global sin esa
            // dependencia.
            ExchangeRateSeeder::class,
            WalletLimitSeeder::class,
            // Requiere que la wallet del jugador ya exista (DemoRoleDataSeeder/
            // TestUserSeeder) y que los límites estén calibrados (arriba) para
            // no chocar contra max_deposit — al final del pipeline.
            DemoWalletBalanceSeeder::class,
        ]);
    }
}
