<?php

namespace App\Console\Commands;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Card\BingoLotRefundFailure;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
use App\Domains\Games\Common\Services\PrizePayoutRetryPolicy;
use App\Exceptions\Wallet\WalletUnavailableException;
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
                ->where('attempts', '<', PrizePayoutRetryPolicy::MAX_ATTEMPTS)
                ->cursor();

            foreach ($failures as $failure) {
                if (!PrizePayoutRetryPolicy::isReadyToRetry($failure->last_attempted_at, $failure->attempts)) {
                    $skipped++;
                    continue;
                }

                // Cachear la sala una sola vez por failure (evita N queries duplicadas en success + abandon path)
                $room = BingoRoom::find($failure->room_id);

                try {
                    $newAttempts = DB::transaction(function () use ($failure, $walletService): int {
                        // lockForUpdate en failure previene que dos ejecuciones concurrentes del cron
                        // procesen el mismo failure simultáneamente.
                        $locked = BingoLotRefundFailure::where('id', $failure->id)
                            ->lockForUpdate()
                            ->first();

                        if (!$locked || $locked->status !== 'PENDING') {
                            // Otro proceso ya resolvió este failure — saltar.
                            return 0;
                        }

                        $newAttempts = $locked->attempts + 1;

                        $lot = BingoCardLot::where('id', $failure->lot_id)
                            ->where('status', 'ACTIVE')
                            ->lockForUpdate()
                            ->first();

                        if (!$lot) {
                            // Ya fue reembolsado por otro proceso — marcar resuelto.
                            $locked->update(['status' => 'RESOLVED', 'resolved_at' => now()]);
                            return 0;
                        }

                        $wallet = $walletService->findActiveWallet(
                            (string) $failure->user_id,
                            (string) $failure->currency_id,
                        );

                        if (!$wallet) {
                            // Actualizar contador DENTRO de la transacción para que sea atómico
                            // con el check del lot. Si la transacción revierte, el contador no queda quemado.
                            $locked->update([
                                'attempts'          => $newAttempts,
                                'last_attempted_at' => now(),
                            ]);
                            throw WalletUnavailableException::notFound(
                                (string) $failure->user_id,
                                (string) $failure->currency_id,
                            );
                        }

                        $walletService->recordRefund(
                            wallet:      $wallet,
                            amount:      (float) $failure->amount,
                            gameId:      (string) $failure->room_id,
                            description: "Reembolso Bingo (reintento) — Sala cancelada #{$failure->room_id}",
                        );

                        $lot->update(['status' => 'REFUNDED']);
                        $locked->update(['status' => 'RESOLVED', 'resolved_at' => now(), 'attempts' => $newAttempts, 'last_attempted_at' => now()]);

                        return $newAttempts;
                    });

                    if ($newAttempts === 0) {
                        // Resuelto por otro proceso o ya procesado — no contar como reintento
                        continue;
                    }

                    $retried++;
                    $this->info("Reembolso #{$failure->id} acreditado exitosamente.");

                    if ($room) {
                        $auditLogger->log($room, BingoRoomAuditLogger::EVENT_REFUND_COMPLETED, 'system', 'Reembolso acreditado por comando de reintento', [
                            'failure_id' => (string) $failure->id,
                            'lot_id'     => (string) $failure->lot_id,
                            'amount'     => (float) $failure->amount,
                            'attempts'   => $newAttempts,
                        ]);
                    }
                } catch (WalletUnavailableException $e) {
                    // Fallo transitorio de infraestructura: NO abandonar aunque se hayan agotado
                    // los intentos — el jugador merece el reembolso cuando el wallet vuelva.
                    $this->warn("Reembolso #{$failure->id}: wallet aún no disponible — se reintentará más tarde.");
                    $skipped++;
                } catch (Throwable $e) {
                    Log::error('[RetryFailedBingoRefunds] Fallo en reintento', [
                        'failure_id' => $failure->id,
                        'error'      => $e->getMessage(),
                    ]);
                    $skipped++;

                    // Recuperar el número de intentos actualizado desde la BD para decidir si abandonar
                    $currentAttempts = BingoLotRefundFailure::where('id', $failure->id)->value('attempts') ?? ($failure->attempts + 1);

                    if (PrizePayoutRetryPolicy::shouldAbandon((int) $currentAttempts)) {
                        BingoLotRefundFailure::where('id', $failure->id)->update(['status' => 'ABANDONED']);
                        $abandoned++;
                        $this->error("Reembolso #{$failure->id} abandonado tras {$currentAttempts} intentos.");

                        if ($room) {
                            $auditLogger->log($room, BingoRoomAuditLogger::EVENT_REFUND_FAILED, 'system', "Reembolso abandonado tras {$currentAttempts} intentos", [
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
