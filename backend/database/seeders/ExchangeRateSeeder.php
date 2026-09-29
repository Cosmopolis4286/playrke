<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

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

    /**
     * Histórico sintético previo al valor ACTIVE de RATES, como
     * [días_atrás => amount] — antes una BD recién sembrada solo tenía la
     * fila ACTIVE (creada aquí mismo), así que /exchange-rate/historical/{id}
     * y el sparkline del widget del dashboard SuperAdmin (ver
     * ExchangeRateSummaryService::buildHistory()) aparecían vacíos hasta la
     * primera edición manual de una tasa — nada que demostrar en una demo
     * fresca. No aplica a USDT: su tasa es fija (1:1) y no tiene
     * variaciones reales que simular.
     */
    private const HISTORY = [
        'VES' => [30 => 165.00, 21 => 170.00, 14 => 172.50, 7 => 176.00],
        'COP' => [30 => 3950.00, 21 => 4000.00, 14 => 4050.00, 7 => 4080.00],
        'BRL' => [30 => 5.40, 21 => 5.45, 14 => 5.50, 7 => 5.55],
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

            $this->seedHistory($isoCode, $currency->id, $admin->id);

            ExchangeRate::create([
                'currency_id' => $currency->id,
                'user_id'     => $admin->id,
                'amount'      => $amount,
                'percentage'  => $this->percentageVsLastHistory($isoCode, $amount),
                'status'      => 'ACTIVE',
            ]);

            $this->command->info("✔ Tasa de cambio: 1 USD = {$amount} {$isoCode}");
        }

        $this->command->info('✅ ExchangeRateSeeder completado.');
    }

    /**
     * Siembra las filas OLD de HISTORY para esta moneda, con created_at/
     * updated_at retrofechados — coherente con cómo
     * ExchangeRateCrudService::update() marca la tasa reemplazada como OLD
     * en un cambio real, así Historical.vue/el sparkline del dashboard ven
     * exactamente el mismo shape de datos que producirían ediciones reales.
     */
    private function seedHistory(string $isoCode, mixed $currencyId, mixed $adminId): void
    {
        $points = self::HISTORY[$isoCode] ?? [];
        $previousAmount = null;

        foreach ($points as $daysAgo => $amount) {
            $timestamp = Carbon::now()->subDays($daysAgo);

            ExchangeRate::create([
                'currency_id' => $currencyId,
                'user_id'     => $adminId,
                'amount'      => $amount,
                'percentage'  => $previousAmount ? round((($amount - $previousAmount) / $previousAmount) * 100, 3) : 0,
                'status'      => 'OLD',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ]);

            $previousAmount = $amount;
        }
    }

    private function percentageVsLastHistory(string $isoCode, float $amount): float
    {
        $points = self::HISTORY[$isoCode] ?? [];

        if (empty($points)) {
            return 0;
        }

        $lastAmount = end($points);

        return round((($amount - $lastAmount) / $lastAmount) * 100, 3);
    }
}
