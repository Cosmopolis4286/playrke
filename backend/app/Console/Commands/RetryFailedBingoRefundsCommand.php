<?php

namespace App\Console\Commands;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Card\BingoLotRefundFailure;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
use App\Domains\Games\Common\Services\PrizePayoutRetryPolicy;
use App\Domains\Wallet\Exceptions\WalletUnavailableException;
use App\Domains\Wallet\Services\WalletService;
use App\Services\Commands\CommandService;
use App\Services\NotificationService;
use App\Traits\ResolvesSuperadminIds;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RetryFailedBingoRefundsCommand extends Command
{
    use ResolvesSuperadminIds;
    protected $description = 'Reintenta acreditar reembolsos de salas canceladas que fallaron en el primer intento';

    protected $signature = 'bingo:retry-failed-refunds';

    public function handle(WalletService $walletService, BingoRoomAuditLogger $auditLogger) : void
    {
        $status = 'SUCCESS';
        $retried = 0;
        $skipped = 0;
        $abandoned = 0;

        try {
            $failures = BingoLotRefundFailure::pending()
                ->where('attempts', '<', PrizePayoutRetryPolicy::MAX_ATTEMPTS)
                ->cursor();

            foreach ($failures as $failure) {
                if (! PrizePayoutRetryPolicy::isReadyToRetry($failure->last_attempted_at, $failure->attempts)) {
                    $skipped++;

                    continue;
                }

                // Cachear la sala una sola vez por failure (evita N queries duplicadas en success + abandon path)
                $room = BingoRoom::find($failure->room_id);

                try {
                    $newAttempts = DB::transaction(function() use ($failure, $walletService) : int {
                        // lockForUpdate en failure previene que dos ejecuciones concurrentes del cron
                        // procesen el mismo failure simultáneamente.
                        $locked = BingoLotRefundFailure::where('id', $failure->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $locked || $locked->status !== 'PENDING') {
                            // Otro proceso ya resolvió este failure — saltar.
                            return 0;
                        }

                        $newAttempts = $locked->attempts + 1;

                        $lot = BingoCardLot::where('id', $failure->lot_id)
                            ->where('status', 'ACTIVE')
                            ->lockForUpdate()
                            ->first();

                        if (! $lot) {
                            // Ya fue reembolsado por otro proceso — marcar resuelto.
                            $locked->update(['status' => 'RESOLVED', 'resolved_at' => now()]);

                            return 0;
                        }

                        $wallet = $walletService->findActiveWallet(
                            (string) $failure->user_id,
                            (string) $failure->currency_id,
                        );

                        if (! $wallet) {
                            // Actualizar contador DENTRO de la transacción para que sea atómico
                            // con el check del lot. Si la transacción revierte, el contador no queda quemado.
                            $locked->update([
                                'attempts' => $newAttempts,
                                'last_attempted_at' => now(),
                            ]);

                            throw WalletUnavailableException::notFound(
                                (string) $failure->user_id,
                                (string) $failure->currency_id,
                            );
                        }

                        $walletService->recordRefund(
                            wallet: $wallet,
                            amount: (float) $failure->amount,
                            gameId: (string) $failure->room_id,
                            description: "Reembolso Bingo (reintento) — Sala cancelada #{$failure->room_id}",
                            bonusPortion: (float) $lot->bonus_amount,
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
                            'lot_id' => (string) $failure->lot_id,
                            'amount' => (float) $failure->amount,
                            'attempts' => $newAttempts,
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
                        'error' => $e->getMessage(),
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
                                'lot_id' => (string) $failure->lot_id,
                                'amount' => (float) $failure->amount,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        // Dedupe natural: el update a ABANDONED de arriba saca esta
                        // fila del scope pending(), así que esta notificación solo
                        // se dispara una vez por reembolso — no hace falta columna
                        // de dedupe adicional (mismo criterio que el resto del
                        // proyecto usa el propio cambio de estado como marca).
                        $this->notifyAbandonedRefund($failure, $currentAttempts, $e->getMessage());
                    }
                }
            }

            $output = "Completado. Acreditados: {$retried}. Aún pendientes: {$skipped}. Abandonados: {$abandoned}.";
        } catch (Throwable $e) {
            $status = 'ERROR';
            $output = sprintf('Error general: %s en %s:%d', $e->getMessage(), $e->getFile(), $e->getLine());
            $this->error($output);
        }

        // Envuelto en try/catch: si logCommandExecution() fallara por
        // cualquier motivo, el error visible al operador debe ser el
        // original de la tarea, no uno de logging que lo enmascare.
        try {
            app(CommandService::class)->logCommandExecution($this->signature, $output, $status);
        } catch (Throwable $logEx) {
            $this->error('No se pudo registrar la ejecución del comando: ' . $logEx->getMessage());
        }

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }

    /**
     * Reembolso agotó reintentos y quedó ABANDONED — antes esto solo
     * quedaba visible en auditoría (BingoRoomAuditLogger), invisible hasta
     * que un admin revisara manualmente. Dinero real de un jugador sin
     * devolver: se notifica a superadmins con los mismos datos que ya se
     * pasan al audit logger, sin queries adicionales.
     */
    private function notifyAbandonedRefund(BingoLotRefundFailure $failure, int $attempts, string $lastError) : void
    {
        $notificationService = app(NotificationService::class);

        foreach ($this->superadminIds() as $superadminId) {
            $notificationService->create(
                userId: $superadminId,
                type: 'SYSTEM',
                title: '💸 Reembolso de Bingo abandonado',
                message: "El reembolso #{$failure->id} (sala #{$failure->room_id}, monto {$failure->amount}) se abandonó tras {$attempts} intentos y requiere acreditación manual.",
                data: [
                    'failure_id' => (string) $failure->id,
                    'room_id' => (string) $failure->room_id,
                    'user_id' => (string) $failure->user_id,
                    'amount' => (float) $failure->amount,
                    'attempts' => $attempts,
                    'last_error' => $lastError,
                    'url' => '/bingo/game-rooms',
                ],
            );
        }
    }
}
