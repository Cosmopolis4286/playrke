<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Tasas de cambio ACTIVE de referencia (unidades de esa moneda por 1 USD).
 *
 * Sin esto, WalletLimitService::recommend() (usado por WalletLimitSeeder
 * para calcular los límites específicos por moneda) lanza
 * ExchangeRateException::noActiveRate() en cualquier BD recién creada —
 * antes de este seeder, las 4 tasas solo existían sembradas a mano vía
 * tinker en un entorno de desarrollo puntual, se perdían en cada
 * migrate:fresh y nadie lo notaba hasta que algo que dependía de ellas
 * fallaba en silencio.
 *
 * Valores aproximados de referencia, pensados para desarrollo/demo — un
 * admin/superadmin los actualiza a los reales desde /exchange-rate
 * (ExchangeRateController) sin tocar este seeder.
 *
 * Debe correr DESPUÉS de TestUserSeeder (user_id de ExchangeRate es
 * NOT NULL, necesita al superadmin demo ya creado) y ANTES de
 * WalletLimitSeeder (ver DatabaseSeeder::run()).
 */
class ExchangeRateSeeder extends Seeder
{
    /** Unidades de esa moneda por 1 USD. USD/USDT no necesitan fila: CurrencyExchangeRateService::getActiveRate() ya las trata como 1:1 sin consultar la tabla. */
    private const RATES = [
        'USDT' => 1.00,
        'VES'  => 180.00,
        'COP'  => 4100.00,
        'BRL'  => 5.60,
    ];

    public function run(): void
    {
        $admin = User::where('email', 'superadmin@playrke.com')->first();

        if (!$admin) {
            $this->command->warn('⚠ superadmin@playrke.com no encontrado — ExchangeRateSeeder requiere que TestUserSeeder corra primero. Omitido.');
            return;
        }

        foreach (self::RATES as $isoCode => $amount) {
            $currency = Currency::where('iso_code', $isoCode)->first();

            if (!$currency) {
                $this->command->warn("⚠ Moneda {$isoCode} no encontrada — ejecuta CurrencySeeder primero. Omitido.");
                continue;
            }

            $exists = ExchangeRate::where('currency_id', $currency->id)
                ->where('status', 'ACTIVE')
                ->exists();

            if ($exists) {
                continue;
            }

            ExchangeRate::create([
                'currency_id' => $currency->id,
                'user_id'     => $admin->id,
                'amount'      => $amount,
                'percentage'  => 0,
                'status'      => 'ACTIVE',
            ]);

            $this->command->info("✔ Tasa de cambio: 1 USD = {$amount} {$isoCode}");
        }

        $this->command->info('✅ ExchangeRateSeeder completado.');
    }
}
