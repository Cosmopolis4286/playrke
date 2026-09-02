<?php

namespace App\Console\Commands\Marketplace;

use App\Domains\Marketplace\Models\Listing;
use App\Domains\Marketplace\Models\Order;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Alerta inconsistencias entre marketplace_listings y marketplace_orders —
 * ver MARKETPLACE_ARCHITECTURE_AUDIT.md sección 24. Solo lectura/alerta,
 * nunca auto-corrección silenciosa (mismo criterio que
 * NotifyStaleWalletMovementsCommand/EscrowReconciliationCommand).
 */
class MarketplaceReconciliationCommand extends Command
{
    protected $signature = 'marketplace:reconcile';

    protected $description = 'Alerta inconsistencias entre listings y orders del marketplace';

    public function handle(): void
    {
        $status = 'SUCCESS';

        try {
            // Listing SOLD sin ninguna Order COMPLETED asociada: el
            // settlement marcó el listing pero no la orden (o viceversa),
            // señal de una excepción entre los dos updates de EscrowService::release().
            $soldWithoutCompletedOrder = Listing::where('status', Listing::STATUS_SOLD)
                ->whereDoesntHave('orders', fn ($q) => $q->where('status', Order::STATUS_COMPLETED))
                ->pluck('id');

            // Order COMPLETED cuyo listing no quedó en SOLD.
            $completedWithoutSoldListing = Order::where('status', Order::STATUS_COMPLETED)
                ->whereHas('listing', fn ($q) => $q->where('status', '!=', Listing::STATUS_SOLD))
                ->pluck('id');

            $total = $soldWithoutCompletedOrder->count() + $completedWithoutSoldListing->count();

            if ($total === 0) {
                $output = 'Sin inconsistencias entre listings y orders.';
            } else {
                if ($soldWithoutCompletedOrder->isNotEmpty()) {
                    Log::warning('[MarketplaceReconciliation] Listings SOLD sin Order COMPLETED', [
                        'listing_ids' => $soldWithoutCompletedOrder->all(),
                    ]);
                }
                if ($completedWithoutSoldListing->isNotEmpty()) {
                    Log::warning('[MarketplaceReconciliation] Orders COMPLETED con listing no SOLD', [
                        'order_ids' => $completedWithoutSoldListing->all(),
                    ]);
                }
                $output = "Se detectaron {$total} inconsistencia(s) — ver logs para detalle.";
                $this->warn($output);
            }
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

        try {
            app(CommandService::class)->logCommandExecution($this->signature, $output, $status);
        } catch (Throwable $logEx) {
            $this->error('No se pudo registrar la ejecución del comando: ' . $logEx->getMessage());
        }

        if ($status === 'SUCCESS' && (!isset($total) || $total === 0)) {
            $this->info($output);
        }
    }
}
