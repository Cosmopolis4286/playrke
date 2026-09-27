<?php

namespace Database\Seeders;

use App\Domains\Wallet\Models\WalletLimit;
use App\Domains\Wallet\Services\WalletLimitService;
use App\Exceptions\Domain\ExchangeRate\ExchangeRateException;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class WalletLimitSeeder extends Seeder
{
    /**
     * Límites globales por nivel KYC (currency_id null = todas las monedas).
     *
     * kyc_level  0 = sin verificar (solo un monto simbólico)
     *            1 = email + teléfono verificados
     *            2 = documento de identidad verificado (KYC completo)
     */
    public function run(): void
    {
        $limits = [
            ['kyc_level' => 0, 'min_deposit' => 1, 'max_deposit' => 100,   'min_per_transaction' => 1, 'max_per_transaction' => 50,   'max_daily' => 50,    'max_weekly' => 100,   'max_monthly' => 200],
            ['kyc_level' => 1, 'min_deposit' => 1, 'max_deposit' => 2000,  'min_per_transaction' => 1, 'max_per_transaction' => 500,  'max_daily' => 1000,  'max_weekly' => 3000,  'max_monthly' => 10000],
            ['kyc_level' => 2, 'min_deposit' => 1, 'max_deposit' => 20000, 'min_per_transaction' => 1, 'max_per_transaction' => 5000, 'max_daily' => 10000, 'max_weekly' => 30000, 'max_monthly' => 100000],
        ];

        foreach ($limits as $limit) {
            WalletLimit::firstOrCreate(
                ['currency_id' => null, 'kyc_level' => $limit['kyc_level']],
                $limit + ['is_active' => true],
            );
        }

        $this->seedPerCurrencyLimits();

        WalletLimit::forgetCatalogCache();

        $this->command->info('✅ WalletLimitSeeder completado.');
    }

    /**
     * Límites específicos por moneda activa, escalados desde el mismo
     * baseline USD que el fallback global vía WalletLimitService::recommend()
     * (no números hardcodeados sueltos, para no divergir de la lógica real
     * que usa el botón "Recomendar" del formulario admin).
     *
     * Sin límites específicos, TODAS las monedas comparten el fallback
     * global de arriba — un depósito de 100 VES pasa el mismo tope que uno
     * de 100 USDT pese a la diferencia de escala real entre ambas. Antes
     * estas 12 filas (4 monedas × 3 niveles KYC) solo existían sembradas a
     * mano vía tinker en un entorno de desarrollo puntual, se perdían en
     * cada migrate:fresh sin que ningún seeder las repusiera.
     *
     * Requiere una tasa de cambio ACTIVE por moneda (ver ExchangeRateSeeder,
     * que corre justo antes en DatabaseSeeder::run()) — si falta, se omite
     * esa moneda con un aviso en vez de fallar todo el seeder completo.
     */
    private function seedPerCurrencyLimits(): void
    {
        $service = app(WalletLimitService::class);
        $currencies = Currency::query()->active()->get(['id', 'iso_code']);

        foreach ($currencies as $currency) {
            foreach ([0, 1, 2] as $kycLevel) {
                $exists = WalletLimit::where('currency_id', $currency->id)
                    ->where('kyc_level', $kycLevel)
                    ->exists();

                if ($exists) {
                    continue;
                }

                try {
                    $recommended = $service->recommend((string) $currency->id, $kycLevel);
                } catch (ExchangeRateException $e) {
                    $this->command->warn("⚠ Sin tasa de cambio activa para {$currency->iso_code} — omitido límite KYC {$kycLevel} (ejecuta ExchangeRateSeeder primero).");
                    continue;
                }

                WalletLimit::create(array_merge($recommended, [
                    'currency_id' => $currency->id,
                    'kyc_level'   => $kycLevel,
                    'is_active'   => true,
                ]));

                $this->command->info("  ✔ Límite {$currency->iso_code} KYC {$kycLevel}");
            }
        }
    }
}
