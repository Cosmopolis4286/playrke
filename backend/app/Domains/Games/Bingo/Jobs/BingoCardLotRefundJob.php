<?php
declare(strict_types=1);
namespace App\Domains\Games\Bingo\Jobs;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Card\BingoLotRefundFailure;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
use App\Exceptions\Wallet\WalletUnavailableException;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\Roles\Player\External\ExternalPlayerParticipation;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reembolsa un único BingoCardLot (cartón comprado) a la wallet del comprador.
 *
 * Se despacha desde BingoRoomCrudService::refundCardLotsForRoom() en lugar de
 * procesar todos los lotes inline — esto desacopla la cancelación de sala del
 * I/O de wallets y evita que timeouts de red bloqueen el HTTP request.
 *
 * Idempotente: lockForUpdate dentro de transacción garantiza que dos workers
 * que procesen el mismo Job concurrentemente no acrediten dos veces la wallet.
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
        $room = BingoRoom::find($this->roomId);

        if (!$room) {
            Log::warning('[BingoCardLotRefundJob] Sala no encontrada', ['room_id' => $this->roomId]);
            return;
        }

        try {
            DB::transaction(function () use ($walletService, $auditLogger, $room) {
                // lockForUpdate garantiza que dos workers concurrentes (requeue tras
                // timeout) no procesen el mismo lot simultáneamente y no dupliquen el pago.
                $lot = BingoCardLot::where('id', $this->lotId)
                    ->lockForUpdate()
                    ->first();

                if (!$lot) {
                    Log::warning('[BingoCardLotRefundJob] Lot no encontrado', ['lot_id' => $this->lotId]);
                    return;
                }

                if ($lot->status !== 'ACTIVE') {
                    // Ya fue reembolsado — idempotencia garantizada.
                    return;
                }

                $amount = (float) $lot->sale_amount;
                if ($amount <= 0) {
                    return;
                }

                $wallet = $walletService->findActiveWallet(
                    (string) $lot->user_id,
                    (string) $lot->currency_id,
                );

                if (!$wallet) {
                    $this->recordFailure($lot, $room, $amount, 'Wallet no encontrada o inactiva');
                    throw WalletUnavailableException::notFound((string) $lot->user_id, (string) $lot->currency_id);
                }

                // El comprador real de este lote puede ser un ExternalPlayer
                // (sin cuenta, sin wallet propia — pagó en efectivo/fuera de
                // banda vía el vendedor). lot->user_id en ese flujo es el
                // Seller/Commerce que ejecutó la compra, NO el comprador
                // final, así que el reembolso automático solo puede acreditar
                // la wallet del vendedor — pero debe quedar marcado
                // explícitamente como dinero de un tercero que el vendedor
                // debe entregar, no como saldo propio ganado.
                $externalBuyer = $this->resolveExternalBuyer($lot);

                $description = $externalBuyer
                    ? "Reembolso Bingo — Sala cancelada #{$room->id} (cartones de jugador externo {$externalBuyer->first_name} — ENTREGAR al comprador, no es saldo propio)"
                    : "Reembolso Bingo — Sala cancelada #{$room->id}";

                $walletService->recordRefund(
                    wallet:        $wallet,
                    amount:        $amount,
                    gameId:        (string) $room->id,
                    description:   $description,
                    bonusPortion:  (float) $lot->bonus_amount,
                    extraMetadata: $externalBuyer ? [
                        'is_external_player_refund' => true,
                        'external_player_id'        => (string) $externalBuyer->id,
                        'external_player_name'      => trim("{$externalBuyer->first_name} {$externalBuyer->last_name}"),
                        'external_player_phone'     => $externalBuyer->phone,
                    ] : [],
                );

                $lot->update(['status' => 'REFUNDED']);

                BingoLotRefundFailure::where('lot_id', $lot->id)
                    ->where('room_id', $room->id)
                    ->whereNull('resolved_at')
                    ->update(['resolved_at' => now(), 'status' => 'RESOLVED']);

                Log::info('[BingoCardLotRefundJob] Reembolso acreditado', [
                    'lot_id'  => $lot->id,
                    'room_id' => $room->id,
                    'amount'  => $amount,
                ]);

                try {
                    $auditLogger->log($room, BingoRoomAuditLogger::EVENT_REFUND_COMPLETED, 'system', 'Reembolso acreditado', [
                        'lot_id' => (string) $lot->id,
                        'amount' => $amount,
                    ]);
                } catch (\Throwable) {}
            });
        } catch (\Throwable $e) {
            Log::error('[BingoCardLotRefundJob] Error al reembolsar', [
                'lot_id'  => $this->lotId,
                'room_id' => $this->roomId,
                'error'   => $e->getMessage(),
            ]);

            $lot = BingoCardLot::find($this->lotId);
            if ($lot) {
                $this->recordFailure($lot, $room, (float) $lot->sale_amount, $e->getMessage());
                try {
                    $auditLogger->log($room, BingoRoomAuditLogger::EVENT_REFUND_FAILED, 'system', 'Reembolso fallido', [
                        'lot_id' => (string) $lot->id,
                        'error'  => $e->getMessage(),
                    ]);
                } catch (\Throwable) {}
            }

            throw $e; // Re-throw para que la queue reintente (3 intentos).
        }
    }

    /**
     * Resuelve el ExternalPlayer comprador real de este lote, si aplica.
     * Un lote vendedor→externo vincula CADA cartón individual a su
     * ExternalPlayer (ver BingoCardGenerationService::generateCards()) — con
     * que uno tenga participación basta para identificar al comprador, ya
     * que todos los cartones de un mismo lote pertenecen al mismo comprador
     * (una sola llamada a generateCards() por compra).
     */
    private function resolveExternalBuyer(BingoCardLot $lot): ?ExternalPlayer
    {
        $firstCardId = BingoCard::where('card_lot_id', $lot->id)->value('id');
        if (!$firstCardId) {
            return null;
        }

        $externalPlayerId = ExternalPlayerParticipation::ofParticipable(BingoCard::class)
            ->where('participable_id', (string) $firstCardId)
            ->value('external_player_id');

        return $externalPlayerId ? ExternalPlayer::find($externalPlayerId) : null;
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
