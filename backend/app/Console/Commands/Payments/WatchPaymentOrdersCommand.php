<?php

namespace App\Console\Commands\Payments;

use App\Domains\Payments\Services\BlockchainWatcherService;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Ejecuta BlockchainWatcherService::watchAll() periódicamente — mecanismo
 * primario de detección de pagos (polling activo, ver
 * PAYMENTS_USDT_TRC20_DESIGN.md sección 5: sin webhook de terceros en este
 * diseño). Registrado en routes/console.php vía schedule_dynamic().
 */
class WatchPaymentOrdersCommand extends Command
{
    protected $description = 'Evalúa las órdenes de pago USDT-TRC20 activas contra la blockchain y avanza su estado';
    protected $signature = 'payments:watch-orders';

    public function handle(BlockchainWatcherService $watcher) : void
    {
        $status = 'SUCCESS';
        $output = 'Órdenes de pago evaluadas correctamente.';

        try {
            $watcher->watchAll();
        } catch (Throwable $e) {
            $status = 'ERROR';
            $output = sprintf(
                'Error al ejecutar la tarea: %s en archivo %s línea %d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
            );
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
