<?php

namespace App\Console\Commands\Payments;

use App\Domains\Payments\Services\UsdtDepositWatcher;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Detecta y acredita depósitos USDT-TRC20 a la billetera (ver
 * UsdtDepositWatcher). No-op si config payments.usdt_deposits no está
 * habilitado. Registrado en routes/console.php cada minuto.
 */
class WatchUsdtDepositsCommand extends Command
{
    protected $description = 'Detecta pagos USDT-TRC20 en la dirección de depósitos y acredita las órdenes';

    protected $signature = 'payments:watch-usdt-deposits';

    public function handle(UsdtDepositWatcher $watcher) : void
    {
        $status = 'SUCCESS';

        try {
            $stats = $watcher->run();
            $output = sprintf(
                'Expiradas: %d · transferencias nuevas: %d · emparejadas: %d · acreditadas: %d',
                $stats['expired'],
                $stats['seen'],
                $stats['matched'],
                $stats['credited'],
            );
        } catch (Throwable $e) {
            $status = 'ERROR';
            $output = 'Error: ' . $e->getMessage();
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
