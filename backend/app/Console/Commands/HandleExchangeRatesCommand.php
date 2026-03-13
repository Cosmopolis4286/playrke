<?php

namespace App\Console\Commands;

use App\Models\UserPreference;
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
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            $this->info('Ningún usuario tiene la actualización automática habilitada.');
            return;
        }

        $rates = $this->apiService->fetch();

        if (empty($rates)) {
            $this->error('No se pudieron obtener las tasas desde la API externa.');
            return;
        }

        $status = 'SUCCESS';

        try {
            foreach ($userIds as $userId) {
                $this->updaterService->updateRates((int) $userId, $rates);
                $this->info("Tasas actualizadas para usuario #{$userId}.");
            }

            $output = "Tasas actualizadas para {$userIds->count()} usuario(s).";
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

        $this->comm->logCommandExecution($this->signature, $output, $status);

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }
}
