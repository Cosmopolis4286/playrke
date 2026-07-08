<?php

namespace App\Console\Commands;

use App\Contracts\WalletRepositoryInterface;
use App\Domains\Games\Bingo\Models\Card\BingoLotRefundFailure;
use App\Services\Commands\CommandService;
use App\Services\WalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RetryFailedBingoRefundsCommand extends Command
{
    private const MAX_ATTEMPTS = 5;

    protected $signature   = 'bingo:retry-failed-refunds';
    protected $description = 'Reintenta acreditar reembolsos de salas canceladas que fallaron en el primer intento';

    public function handle(WalletService $walletService, WalletRepositoryInterface $walletRepository): void
    {
        $status    = 'SUCCESS';
        $retried   = 0;
        $skipped   = 0;
        $abandoned = 0;

        try {
            $failures = BingoLotRefundFailure::pending()
                ->where('attempts', '<', self::MAX_ATTEMPTS)
                ->get();

            foreach ($failures as $failure) {
                $failure->increment('attempts');
                $failure->last_attempted_at = now();
                $failure->save();

                try {
                    $wallet = $walletRepository->findWalletByUserAndCurrency(
                        (string) $failure->user_id,
                        (string) $failure->currency_id,
                    );

                    if (!$wallet || !$wallet->isActive()) {
                        $this->warn("Reembolso #{$failure->id}: wallet aún no disponible.");
                        $skipped++;
                        continue;
                    }

                    $walletService->recordRefund(
                        wallet:      $wallet,
                        amount:      (float) $failure->amount,
                        gameId:      (string) $failure->room_id,
                        description: "Reembolso Bingo (reintento) — Sala cancelada #{$failure->room_id}",
                    );

                    $failure->update(['status' => 'RESOLVED', 'resolved_at' => now()]);
                    $retried++;
                    $this->info("Reembolso #{$failure->id} acreditado exitosamente.");
                } catch (Throwable $e) {
                    Log::error('[RetryFailedBingoRefunds] Fallo en reintento', [
                        'failure_id' => $failure->id,
                        'error'      => $e->getMessage(),
                    ]);
                    $skipped++;

                    if ($failure->attempts >= self::MAX_ATTEMPTS) {
                        $failure->update(['status' => 'ABANDONED']);
                        $abandoned++;
                        $this->error("Reembolso #{$failure->id} abandonado tras {$failure->attempts} intentos.");
                    }
                }
            }

            $output = "Completado. Acreditados: {$retried}. Aún pendientes: {$skipped}. Abandonados: {$abandoned}.";
        } catch (Throwable $e) {
            $status = 'ERROR';
            $output = sprintf('Error general: %s en %s:%d', $e->getMessage(), $e->getFile(), $e->getLine());
            $this->error($output);
        }

        app(CommandService::class)->logCommandExecution($this->signature, $output, $status);

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }
}
