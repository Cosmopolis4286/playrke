<?php
declare(strict_types=1);
namespace App\Domains\Games\Bingo\Jobs;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Card\BingoLotRefundFailure;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Reembolsa un único BingoCardLot (cartón comprado) a la wallet del comprador.
 *
 * Se despacha desde BingoRoomCrudService::refundCardLotsForRoom() en lugar de
 * procesar todos los lotes inline — esto desacopla la cancelación de sala del
 * I/O de wallets y evita que timeouts de red bloqueen el HTTP request.
 *
 * Idempotente: verifica que el lot siga en estado ACTIVE antes de acreditar.
 */
final class BingoCardLotRefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        private readonly string $lotId,
        private readonly string $roomId,
    ) {}

    public function handle(WalletService $walletService, BingoRoomAuditLogger $auditLogger): void
    {
        $lot  = BingoCardLot::find($this->lotId);
        $room = BingoRoom::find($this->roomId);

        if (!$lot || !$room) {
            Log::warning('[BingoCardLotRefundJob] Lot o sala no encontrada', [
                'lot_id'  => $this->lotId,
                'room_id' => $this->roomId,
            ]);
            return;
        }

        if ($lot->status !== 'ACTIVE') {
            // Ya fue reembolsado por un reintento anterior — idempotencia.
            return;
        }

        $amount = (float) $lot->sale_amount;
        if ($amount <= 0) {
            return;
        }

        try {
            $wallet = $walletService->findActiveWallet(
                (string) $lot->user_id,
                (string) $lot->currency_id,
            );

            if (!$wallet) {
                $this->recordFailure($lot, $room, $amount, 'Wallet no encontrada o inactiva');
                return;
            }

            $walletService->recordRefund(
                wallet:      $wallet,
                amount:      $amount,
                gameId:      (string) $room->id,
                description: "Reembolso Bingo — Sala cancelada #{$room->id}",
            );

            $lot->update(['status' => 'REFUNDED']);

            BingoLotRefundFailure::where('lot_id', $lot->id)
                ->where('room_id', $room->id)
                ->whereNull('resolved_at')
                ->update(['resolved_at' => now(), 'status' => 'RESOLVED']);

            $auditLogger->log($room, BingoRoomAuditLogger::EVENT_REFUND_COMPLETED, 'system', 'Reembolso acreditado', [
                'lot_id' => (string) $lot->id,
                'amount' => $amount,
            ]);

            Log::info('[BingoCardLotRefundJob] Reembolso acreditado', [
                'lot_id'  => $lot->id,
                'room_id' => $room->id,
                'amount'  => $amount,
            ]);
        } catch (\Throwable $e) {
            Log::error('[BingoCardLotRefundJob] Error al reembolsar', [
                'lot_id'  => $lot->id,
                'room_id' => $room->id,
                'error'   => $e->getMessage(),
            ]);
            $this->recordFailure($lot, $room, $amount, $e->getMessage());

            try {
                $auditLogger->log($room, BingoRoomAuditLogger::EVENT_REFUND_FAILED, 'system', 'Reembolso fallido', [
                    'lot_id' => (string) $lot->id,
                    'amount' => $amount,
                    'error'  => $e->getMessage(),
                ]);
            } catch (\Throwable) {
                // No interrumpir el flujo de error original si el audit falla
            }

            throw $e; // Re-throw para que la queue reintente (3 intentos).
        }
    }

    private function recordFailure(BingoCardLot $lot, BingoRoom $room, float $amount, string $reason): void
    {
        BingoLotRefundFailure::updateOrCreate(
            ['lot_id' => $lot->id, 'room_id' => $room->id],
            [
                'user_id'           => $lot->user_id,
                'currency_id'       => $lot->currency_id,
                'amount'            => $amount,
                'error_message'     => $reason,
                'status'            => 'PENDING',
                'attempts'          => \Illuminate\Support\Facades\DB::raw('COALESCE(attempts, 0) + 1'),
                'last_attempted_at' => now(),
            ]
        );
    }
}
