<?php

namespace App\Console\Commands;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Card\BingoLotRefundFailure;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
use App\Domains\Games\Bingo\Services\Winner\BingoPrizePayoutRetryPolicy;
use App\Services\Commands\CommandService;
use App\Services\WalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RetryFailedBingoRefundsCommand extends Command
{
    protected $signature   = 'bingo:retry-failed-refunds';
    protected $description = 'Reintenta acreditar reembolsos de salas canceladas que fallaron en el primer intento';

    public function handle(WalletService $walletService, BingoRoomAuditLogger $auditLogger): void
    {
        $status    = 'SUCCESS';
        $retried   = 0;
        $skipped   = 0;
        $abandoned = 0;

        try {
            $failures = BingoLotRefundFailure::pending()
                ->where('attempts', '<', BingoPrizePayoutRetryPolicy::MAX_ATTEMPTS)
                ->cursor();

            foreach ($failures as $failure) {
                // Respetar el back-off lineal entre intentos.
                if (!BingoPrizePayoutRetryPolicy::isReadyToRetry($failure->last_attempted_at, $failure->attempts)) {
                    $skipped++;
                    continue;
                }

                $newAttempts = $failure->attempts + 1;
                $failure->update([
                    'attempts'          => $newAttempts,
                    'last_attempted_at' => now(),
                ]);

                try {
                    DB::transaction(function () use ($failure, $walletService) {
                        // lockForUpdate previene doble reembolso si cancel() y este comando
                        // corren concurrentemente sobre el mismo lot.
                        $lot = BingoCardLot::where('id', $failure->lot_id)
                            ->where('status', 'ACTIVE')
                            ->lockForUpdate()
                            ->first();

                        if (!$lot) {
                            // Ya fue reembolsado por otro proceso
                            $failure->update(['status' => 'RESOLVED', 'resolved_at' => now()]);
                            return;
                        }

                        $wallet = $walletService->findActiveWallet(
                            (string) $failure->user_id,
                            (string) $failure->currency_id,
                        );

                        if (!$wallet) {
                            throw new \RuntimeException('Wallet no disponible');
                        }

                        $walletService->recordRefund(
                            wallet:      $wallet,
                            amount:      (float) $failure->amount,
                            gameId:      (string) $failure->room_id,
                            description: "Reembolso Bingo (reintento) — Sala cancelada #{$failure->room_id}",
                        );

                        $lot->update(['status' => 'REFUNDED']);
                        $failure->update(['status' => 'RESOLVED', 'resolved_at' => now()]);
                    });

                    $retried++;
                    $this->info("Reembolso #{$failure->id} acreditado exitosamente.");

                    $room = BingoRoom::find($failure->room_id);
                    if ($room) {
                        $auditLogger->log($room, BingoRoomAuditLogger::EVENT_REFUND_COMPLETED, 'system', 'Reembolso acreditado por comando de reintento', [
                            'failure_id' => (string) $failure->id,
                            'lot_id'     => (string) $failure->lot_id,
                            'amount'     => (float) $failure->amount,
                            'attempts'   => $newAttempts,
                        ]);
                    }
                } catch (Throwable $e) {
                    $isWalletUnavailable = $e instanceof \RuntimeException
                        && str_contains($e->getMessage(), 'Wallet');

                    if ($isWalletUnavailable) {
                        $this->warn("Reembolso #{$failure->id}: wallet aún no disponible.");
                    } else {
                        Log::error('[RetryFailedBingoRefunds] Fallo en reintento', [
                            'failure_id' => $failure->id,
                            'error'      => $e->getMessage(),
                        ]);
                    }

                    $skipped++;

                    if (BingoPrizePayoutRetryPolicy::shouldAbandon($newAttempts)) {
                        $failure->update(['status' => 'ABANDONED']);
                        $abandoned++;
                        $this->error("Reembolso #{$failure->id} abandonado tras {$newAttempts} intentos.");

                        $room = BingoRoom::find($failure->room_id);
                        if ($room) {
                            $auditLogger->log($room, BingoRoomAuditLogger::EVENT_REFUND_FAILED, 'system', "Reembolso abandonado tras {$newAttempts} intentos", [
                                'failure_id' => (string) $failure->id,
                                'lot_id'     => (string) $failure->lot_id,
                                'amount'     => (float) $failure->amount,
                                'error'      => $e->getMessage(),
                            ]);
                        }
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
