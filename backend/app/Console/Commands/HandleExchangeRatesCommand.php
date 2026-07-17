<?php

namespace App\Console\Commands;

use App\Models\UserPreference;
use App\Services\Commands\CommandService;
use App\Services\ExchangeRate\BcvExchangeRateService;
use App\Services\ExchangeRate\ExchangeRateApiService;
use App\Services\ExchangeRate\ExchangeRateUpdaterService;
use Illuminate\Console\Command;
use Throwable;

class HandleExchangeRatesCommand extends Command
{
    protected $signature = 'handle:exchange-rates';

    protected $description = 'Actualiza las tasas de cambio USD para los usuarios con auto-actualización habilitada';

    public function __construct(
        private readonly ExchangeRateApiService $apiService,
        private readonly BcvExchangeRateService $bcvApiService,
        private readonly ExchangeRateUpdaterService $updaterService,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $userIds = UserPreference::query()
            ->where('category', UserPreference::CATEGORY_EXCHANGE_RATE)
            ->where('key', 'auto_update_enabled')
            ->where('value', 'true')
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $bcvUserIds = UserPreference::query()
            ->where('category', UserPreference::CATEGORY_EXCHANGE_RATE)
            ->where('key', 'bcv_auto_update_enabled')
            ->where('value', 'true')
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn($id) => (int) $id)
            ->all();

        // Usuarios que solo activaron BCV (VES) sin la actualización general (Binance).
        $bcvOnlyUserIds = array_values(array_diff($bcvUserIds, $userIds));

        if (empty($userIds) && empty($bcvOnlyUserIds)) {
            $this->info('Ningún usuario tiene la actualización automática habilitada.');
            return;
        }

        $rates = !empty($userIds) ? $this->apiService->fetch() : null;
        $ratesFetchFailed = !empty($userIds) && empty($rates);

        if ($ratesFetchFailed) {
            $this->error('No se pudieron obtener las tasas desde la API externa.');
        }

        $bcvRate = !empty($bcvUserIds) ? $this->bcvApiService->fetch() : null;
        $bcvFetchFailed = !empty($bcvUserIds) && $bcvRate === null;

        if ($bcvFetchFailed) {
            $this->error('No se pudo obtener la tasa oficial del BCV.');
        }

        if ($ratesFetchFailed && empty($bcvOnlyUserIds)) {
            app(CommandService::class)->logCommandExecution(
                $this->signature,
                'No se pudieron obtener las tasas desde la API externa.',
                'ERROR'
            );
            return;
        }

        if (empty($rates) && $bcvFetchFailed) {
            app(CommandService::class)->logCommandExecution(
                $this->signature,
                'No se pudieron obtener las tasas desde la API externa ni la tasa del BCV.',
                'ERROR'
            );
            return;
        }

        $updatedCount = 0;
        $status = 'SUCCESS';

        try {
            // Usuarios con actualización general (Binance), sustituyendo VES por BCV si aplica.
            if (!empty($rates)) {
                foreach ($userIds as $userId) {
                    $userRates = $rates;

                    if ($bcvRate !== null && in_array($userId, $bcvUserIds, true)) {
                        $userRates['VES'] = $bcvRate;
                    }

                    $this->updaterService->updateRates($userId, $userRates);
                    $this->info("Tasas actualizadas para usuario #{$userId}.");
                    $updatedCount++;
                }
            }

            // Usuarios que solo activaron BCV: solo se actualiza VES.
            if ($bcvRate !== null) {
                foreach ($bcvOnlyUserIds as $userId) {
                    $this->updaterService->updateRates($userId, ['VES' => $bcvRate]);
                    $this->info("Tasa VES (BCV) actualizada para usuario #{$userId}.");
                    $updatedCount++;
                }
            }

            $output = "Tasas actualizadas para {$updatedCount} usuario(s).";
        } catch (Throwable $e) {
            $status = 'ERROR';
            $output = sprintf(
                'Error al ejecutar la tarea: %s en archivo %s línea %d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            $this->error($output);
        }

        app(CommandService::class)->logCommandExecution($this->signature, $output, $status);

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }
}
