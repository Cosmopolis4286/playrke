<?php

namespace App\Console\Commands;

use App\Domains\Games\Bingo\Jobs\BingoCardLotRefundJob;
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
                ->where(function ($query) {
                    $query->where('attempts', '<', PrizePayoutRetryPolicy::MAX_ATTEMPTS)
                        // Una wallet inactiva/no disponible es transitoria: no se
                        // abandona el reembolso ni se deja de reintentar al llegar
                        // al máximo ordinario de fallos.
                        ->orWhere('error_message', 'WALLET_UNAVAILABLE');
                })
                ->cursor();

            foreach ($failures as $failure) {
                if (! PrizePayoutRetryPolicy::isReadyToRetry($failure->last_attempted_at, $failure->attempts)) {
                    $skipped++;

                    continue;
                }

                $room = BingoRoom::find($failure->room_id);
                if (! $room) {
                    Log::warning('[RetryFailedBingoRefunds] Sala no encontrada para reintentar reembolso', [
                        'failure_id' => (string) $failure->id,
                        'room_id' => (string) $failure->room_id,
                    ]);
                    $skipped++;

                    continue;
                }

                try {
                    // Reutilizar el job original evita duplicar la lógica financiera.
                    // El job revierte métricas, comisiones, RKE/jackpot, registra
                    // obligaciones de compradores externos y acredita la wallet.
                    (new BingoCardLotRefundJob(
                        (string) $failure->lot_id,
                        (string) $failure->room_id,
                    ))->handle($walletService, $auditLogger);

                    $failure->refresh();
                    if ($failure->status === 'RESOLVED') {
                        $retried++;
                        $this->info("Reembolso #{$failure->id} procesado correctamente.");
                    } else {
                        // El job puede terminar sin movimiento si otro proceso ya
                        // procesó el lote. No declarar éxito mientras siga pendiente.
                        $skipped++;
                    }
                } catch (WalletUnavailableException $e) {
                    // La indisponibilidad de wallet es transitoria: no abandonar
                    // el reembolso, aunque se hayan agotado los intentos ordinarios.
                    $this->warn("Reembolso #{$failure->id}: wallet aún no disponible; se reintentará más tarde.");
                    $skipped++;
                } catch (Throwable $e) {
                    Log::error('[RetryFailedBingoRefunds] Fallo en reintento', [
                        'failure_id' => (string) $failure->id,
                        'error' => $e->getMessage(),
                    ]);
                    $skipped++;

                    // El job registra el intento fallido. Consultar el valor persistido
                    // evita calcular intentos sobre una instancia obsoleta.
                    $current = BingoLotRefundFailure::find($failure->id);
                    $currentAttempts = (int) ($current?->attempts ?? $failure->attempts);

                    if ($current && PrizePayoutRetryPolicy::shouldAbandon($currentAttempts)) {
                        $updated = BingoLotRefundFailure::whereKey($current->id)
                            ->where('status', 'PENDING')
                            ->update(['status' => 'ABANDONED']);
                        if ($updated > 0) {
                            $abandoned++;
                            $this->error("Reembolso #{$failure->id} abandonado tras {$currentAttempts} intentos.");

                            try {
                                $auditLogger->log($room, BingoRoomAuditLogger::EVENT_REFUND_FAILED, 'system', "Reembolso abandonado tras {$currentAttempts} intentos", [
                                    'failure_id' => (string) $failure->id,
                                    'lot_id' => (string) $failure->lot_id,
                                    'amount' => (float) $failure->amount,
                                    'error' => $e->getMessage(),
                                ]);
                            } catch (Throwable $auditException) {
                                Log::warning('[RetryFailedBingoRefunds] No se pudo registrar auditoría de abandono', [
                                    'failure_id' => (string) $failure->id,
                                    'error' => $auditException->getMessage(),
                                ]);
                            }

                            $this->notifyAbandonedRefund($failure, $currentAttempts, $e->getMessage());
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
     * Un reembolso agotó los reintentos y quedó ABANDONED: notificar a
     * superadmins porque el dinero del jugador requiere intervención manual.
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
