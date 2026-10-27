<?php

namespace App\Console\Commands;

use App\Services\Commands\CommandService;
use App\Services\ExchangeRate\ExchangeRateAlertService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Alertas de tasas de cambio: cambios a la red, tasas propias
 * desactualizadas y desvíos vs. la oficial. Ver ExchangeRateAlertService.
 */
class ExchangeRateAlertsCommand extends Command
{
    protected $description = 'Avisa cambios de tasa a la red y tasas propias desactualizadas o alejadas de la oficial';
    protected $signature = 'exchange-rates:alerts';

    public function handle(ExchangeRateAlertService $service) : void
    {
        $status = 'SUCCESS';

        try {
            $r = $service->run();
            $output = "Completado. Cambios oficiales: {$r['official_changes']}. Cambios propios: {$r['own_changes']}. "
                . "Desactualizadas: {$r['stale']}. Desvíos: {$r['deviations']}. Notificaciones: {$r['notified']}.";
        } catch (Throwable $e) {
            $status = 'ERROR';
            $output = sprintf('Error general: %s en %s:%d', $e->getMessage(), $e->getFile(), $e->getLine());
            $this->error($output);
        }

        try {
            app(CommandService::class)->logCommandExecution($this->signature, $output, $status);
        } catch (Throwable $logEx) {
            $this->error('No se pudo registrar la ejecución del comando: ' . $logEx->getMessage());
        }

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }
}
