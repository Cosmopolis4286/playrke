<?php

namespace App\Console\Commands\Marketplace;

use App\Domains\Marketplace\Crypto\Models\CryptoTransaction;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Alerta (nunca auto-corrige) crypto_transactions confirmadas on-chain que
 * llevan demasiado tiempo sin acreditarse internamente — señal de un fallo
 * parcial entre CONFIRMED y CREDITED (ver DepositWatcher::process(), que
 * hace ambos pasos en una sola transacción DB, así que este estado
 * intermedio solo debería ser posible si el proceso murió a mitad de esa
 * transacción). Mismo patrón que EscrowReconciliationCommand/
 * NotifyStaleWalletMovementsCommand.
 */
class CryptoDepositReconciliationCommand extends Command
{
    private const STALE_MINUTES = 15;

    protected $description = 'Alerta depósitos cripto confirmados on-chain sin acreditar internamente';
    protected $signature = 'marketplace:reconcile-crypto-deposits';

    public function handle() : void
    {
        $status = 'SUCCESS';

        try {
            $stale = CryptoTransaction::where('status', CryptoTransaction::STATUS_CONFIRMED)
                ->where('direction', CryptoTransaction::DIRECTION_DEPOSIT)
                ->where('confirmed_at', '<=', now()->subMinutes(self::STALE_MINUTES))
                ->get(['id', 'tx_hash', 'user_id', 'amount', 'confirmed_at']);

            if ($stale->isEmpty()) {
                $output = 'Sin depósitos cripto confirmados pendientes de acreditar.';
            } else {
                foreach ($stale as $tx) {
                    Log::warning('[CryptoDepositReconciliation] Depósito confirmado sin acreditar', [
                        'crypto_transaction_id' => $tx->id,
                        'tx_hash' => $tx->tx_hash,
                        'user_id' => $tx->user_id,
                        'amount' => $tx->amount,
                        'confirmed_at' => $tx->confirmed_at?->toIso8601String(),
                    ]);
                }
                $output = "Se detectaron {$stale->count()} depósito(s) cripto confirmados sin acreditar — ver logs para detalle.";
                $this->warn($output);
            }
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

        if ($status === 'SUCCESS' && (! isset($stale) || $stale->isEmpty())) {
            $this->info($output);
        }
    }
}
