<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\User;
use App\Services\ExchangeRate\BcvExchangeRateService;
use Illuminate\Console\Command;

/**
 * Comando manual (no seeder, no programado) para refrescar la tasa VES
 * sembrada por ExchangeRateSeeder con el valor real publicado por el BCV.
 *
 * Deliberadamente separado del seeder: ExchangeRateSeeder corre en
 * migrate:fresh --seed, incluido en CI/testing — hacer una petición HTTP a
 * un proveedor externo ahí haría el pipeline no determinista y frágil ante
 * caídas de bcv.org.ve/dolarapi.com (ver BcvExchangeRateService). Este
 * comando es para correrlo a mano después de sembrar una BD de desarrollo/
 * demo, cuando se quiere que el valor de referencia inicial sea real en vez
 * del aproximado fijo de ExchangeRateSeeder::RATES.
 *
 * Solo actualiza VES (la única moneda con fuente oficial gratuita conocida
 * en este dominio, ver BcvExchangeRateService) — COP/BRL se quedan con el
 * valor del seeder; USDT es 1:1 fijo y no necesita refresco.
 */
class RefreshDemoExchangeRatesCommand extends Command
{
    protected $description = 'Actualiza la tasa VES sembrada con el valor real publicado por el BCV (uso manual, no forma parte de db:seed)';
    protected $signature = 'exchange-rate:refresh-demo-ves';

    public function __construct(
        private readonly BcvExchangeRateService $bcvService,
    ) {
        parent::__construct();
    }

    public function handle() : int
    {
        $currency = Currency::where('iso_code', 'VES')->first();

        if (! $currency) {
            $this->error('Moneda VES no encontrada — ejecuta CurrencySeeder primero.');

            return self::FAILURE;
        }

        $this->info('Consultando tasa oficial del BCV...');
        $rate = $this->bcvService->fetch();

        if ($rate === null) {
            $this->error('No se pudo obtener la tasa del BCV (ni del scraping oficial ni del fallback). Intenta más tarde.');

            return self::FAILURE;
        }

        $admin = User::where('email', 'superadmin@playrke.com')->first();

        if (! $admin) {
            $this->error('superadmin@playrke.com no encontrado — ejecuta TestUserSeeder primero.');

            return self::FAILURE;
        }

        $current = ExchangeRate::where('currency_id', $currency->id)
            ->where('status', 'ACTIVE')
            ->first();

        if ($current) {
            $current->update(['status' => 'OLD']);
        }

        $previousAmount = $current ? (float) $current->amount : null;

        ExchangeRate::create([
            'currency_id' => $currency->id,
            'user_id' => $admin->id,
            'amount' => $rate,
            'percentage' => $previousAmount ? round((($rate - $previousAmount) / $previousAmount) * 100, 3) : 0,
            'status' => 'ACTIVE',
        ]);

        $this->info("✔ Tasa VES actualizada: 1 USD = {$rate} VES (fuente: BCV).");

        return self::SUCCESS;
    }
}
