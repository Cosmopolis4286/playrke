<?php

declare(strict_types=1);

namespace App\Domains\Games\Bingo\Jobs;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Card\BingoLotRefundFailure;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
use App\Domains\Games\Bingo\Services\Payment\ExternalPlayerLotBuyerResolver;
use App\Domains\Games\Bingo\Services\Room\Metrics\BingoLotSaleReversalService;
use App\Domains\Wallet\Exceptions\WalletUnavailableException;
use App\Domains\Wallet\Services\WalletService;
use App\Models\Roles\Player\External\ExternalPlayer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Revierte financieramente un único BingoCardLot (cartón comprado).
 *
 * En ventas con wallet acredita al comprador. En ventas offline de jugadores
 * externos, devuelve al vendedor lo que la plataforma ya había cobrado y abre
 * una conciliación pendiente para confirmar que el vendedor entregó al jugador
 * externo el importe completo que recibió.
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
    public int $timeout = 30;

    public int $tries = 3;

    public function __construct(
        private readonly string $lotId,
        private readonly string $roomId,
    ) {
    }

    public function handle(WalletService $walletService, BingoRoomAuditLogger $auditLogger) : void
    {
        $room = BingoRoom::find($this->roomId);

        if (! $room) {
            Log::warning('[BingoCardLotRefundJob] Sala no encontrada', ['room_id' => $this->roomId]);

            return;
        }

        try {
            DB::transaction(function() use ($walletService, $auditLogger, $room) {
                // lockForUpdate garantiza que dos workers concurrentes (requeue tras
                // timeout) no procesen el mismo lot simultáneamente y no dupliquen el pago.
                $lot = BingoCardLot::where('id', $this->lotId)
                    ->lockForUpdate()
                    ->first();

                if (! $lot) {
                    Log::warning('[BingoCardLotRefundJob] Lot no encontrado', ['lot_id' => $this->lotId]);

                    return;
                }

                if ($lot->status !== 'ACTIVE') {
                    // Un proceso anterior pudo completar el reembolso y fallar
                    // antes de resolver una fila histórica de fallos.
                    if ($lot->status === 'REFUNDED') {
                        BingoLotRefundFailure::where('lot_id', $lot->id)
                            ->where('room_id', $room->id)
                            ->where('status', 'PENDING')
                            ->whereNull('resolved_at')
                            ->update([
                                'status' => 'RESOLVED',
                                'resolved_at' => now(),
                            ]);
                    }

                    // Idempotencia: nunca volver a acreditar un lote no activo.
                    return;
                }

                if ((float) $lot->sale_amount <= 0) {
                    // Un lote sin importe también debe cerrar el ciclo: dejarlo ACTIVE
                    // provoca reintentos inútiles y conserva fallos históricos pendientes.
                    $this->completeLotRefund(
                        $lot,
                        $room,
                        $auditLogger,
                        0.0,
                        'Reembolso sin importe: lote marcado como reembolsado',
                    );

                    return;
                }

                // Revertir primero lo que esta venta sumó (métricas y bote de la
                // sala, BingoCreatorSale, RKE/fondeo de plataforma, jackpot) — en
                // la misma transacción: si algo falla, el reembolso se reintenta entero.
                $reversal = app(BingoLotSaleReversalService::class)->reverse($lot, $room);

                // Cuánto acreditar y a quién:
                //  - Venta con billetera (o lote previo sin desglose): el total de la
                //    venta a la wallet del comprador, como siempre.
                //  - Venta fuera del sistema (vendedor → jugador externo): el dinero
                //    lo cobró el comercio en efectivo y lo sigue teniendo — antes
                //    igual se le acreditaba el total en su wallet (y en un pago
                //    RECHAZADO, plata que nunca cobró). Ahora solo se le reintegra
                //    lo que la plataforma ya le había cobrado de ese lote; el resto
                //    se lo devuelve al comprador de su efectivo.
                $offline = $reversal['channel'] === 'offline';
                $amount = $offline ? (float) $reversal['refund_to_seller'] : (float) $lot->sale_amount;
                $externalBuyer = $this->resolveExternalBuyer($lot);

                if ($amount <= 0) {
                    if ($offline && $externalBuyer) {
                        $this->recordExternalBuyerRefundObligation($lot, $room, $externalBuyer, $amount);
                    }

                    $this->completeLotRefund(
                        $lot,
                        $room,
                        $auditLogger,
                        0.0,
                        $offline && $externalBuyer
                            ? 'Reversión contable completada; devolución al comprador externo pendiente de confirmación'
                            : 'Reembolso sin acreditación: el comercio devuelve al comprador el efectivo que cobró',
                    );

                    return;
                }

                $wallet = $walletService->findActiveWallet(
                    (string) $lot->user_id,
                    (string) $lot->currency_id,
                );

                if (! $wallet) {
                    // El catch exterior registra el fallo y aumenta attempts una
                    // sola vez. No duplicar el registro antes de propagar la excepción.
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
                $description = match (true) {
                    $offline => "Reembolso Bingo — Sala #{$room->id}: reintegro de lo cobrado por la plataforma"
                        . ($externalBuyer ? " (jugador externo {$externalBuyer->first_name} — ENTREGAR al comprador junto con el efectivo retenido, no es saldo propio)" : ''),
                    (bool) $externalBuyer => "Reembolso Bingo — Sala cancelada #{$room->id} (cartones de jugador externo {$externalBuyer->first_name} — ENTREGAR al comprador, no es saldo propio)",
                    default => "Reembolso Bingo — Sala cancelada #{$room->id}",
                };

                $walletService->recordRefund(
                    wallet: $wallet,
                    amount: $amount,
                    gameId: (string) $room->id,
                    description: $description,
                    // En fuera del sistema no hubo bono de billetera involucrado.
                    bonusPortion: $offline ? 0.0 : (float) $lot->bonus_amount,
                    extraMetadata: $externalBuyer ? [
                        'is_external_player_refund' => true,
                        'external_player_id' => (string) $externalBuyer->id,
                        'external_player_name' => trim("{$externalBuyer->first_name} {$externalBuyer->last_name}"),
                        'external_player_phone' => $externalBuyer->phone,
                    ] : [],
                );

                if ($offline && $externalBuyer) {
                    $this->recordExternalBuyerRefundObligation($lot, $room, $externalBuyer, $amount);
                }

                $this->completeLotRefund(
                    $lot,
                    $room,
                    $auditLogger,
                    $amount,
                    $offline && $externalBuyer
                        ? 'Reversión contable completada; devolución al comprador externo pendiente de confirmación'
                        : 'Reembolso acreditado',
                );
            });
        } catch (Throwable $e) {
            Log::error('[BingoCardLotRefundJob] Error al reembolsar', [
                'lot_id' => $this->lotId,
                'room_id' => $this->roomId,
                'error' => $e->getMessage(),
            ]);

            $lot = BingoCardLot::find($this->lotId);
            if ($lot) {
                $this->recordFailure($lot, $room, (float) $lot->sale_amount, $e->getMessage());

                try {
                    $auditLogger->log($room, BingoRoomAuditLogger::EVENT_REFUND_FAILED, 'system', 'Reembolso fallido', [
                        'lot_id' => (string) $lot->id,
                        'error' => $e->getMessage(),
                    ]);
                } catch (Throwable) {
                }
            }

            throw $e; // Re-throw para que la queue reintente (3 intentos).
        }
    }

    /**
     * La reversión de una venta offline devuelve al vendedor lo que ya había
     * transferido a la plataforma, pero el comprador externo no tiene wallet.
     * El vendedor debe entregarle el importe completo de la venta. Se abre una
     * conciliación pendiente para no confundir la reversión contable con la
     * devolución física del dinero al comprador.
     */
    private function recordExternalBuyerRefundObligation(
        BingoCardLot $lot,
        BingoRoom $room,
        ExternalPlayer $buyer,
        float $platformReimbursement,
    ) : void {
        $item = app(\App\Services\Platform\FinancialReconciliationService::class)->record(
            type: \App\Models\Platform\FinancialReconciliationItem::TYPE_EXTERNAL_PLAYER_REFUND_PENDING,
            message: "Devolución externa pendiente: vendedor #" . ($lot->seller_user_id ?: $lot->user_id) . " debe entregar a {$buyer->first_name} {$buyer->last_name} el importe completo del lote #{$lot->id}.",
            amount: (float) $lot->sale_amount,
            currencyId: $lot->currency_id ? (string) $lot->currency_id : null,
            roomId: (string) $room->id,
            lotId: (string) $lot->id,
            idempotencyKey: "external-player-refund-delivery-{$lot->id}",
            details: [
                'external_player_id' => (string) $buyer->id,
                'seller_user_id' => (string) ($lot->seller_user_id ?: $lot->user_id),
                'buyer_refund_amount' => (float) $lot->sale_amount,
                'platform_reimbursement_to_seller' => round($platformReimbursement, 2),
                'delivery_confirmation_required' => true,
            ],
        );

        if (! $item) {
            throw new RuntimeException('No se pudo registrar la devolución pendiente del comprador externo.');
        }
    }

    /** Marca el lote REFUNDED, resuelve fallas previas y deja log + auditoría. */
    private function completeLotRefund(BingoCardLot $lot, BingoRoom $room, BingoRoomAuditLogger $auditLogger, float $amount, string $message) : void
    {
        $lot->update(['status' => 'REFUNDED']);

        // El evento updated de BingoCardLot sincroniza contadores y finanzas
        // dentro de esta misma transacción al cambiar ACTIVE -> REFUNDED.

        BingoLotRefundFailure::where('lot_id', $lot->id)
            ->where('room_id', $room->id)
            ->whereNull('resolved_at')
            ->update(['resolved_at' => now(), 'status' => 'RESOLVED']);

        Log::info('[BingoCardLotRefundJob] ' . $message, [
            'lot_id' => $lot->id,
            'room_id' => $room->id,
            'amount' => $amount,
        ]);

        try {
            $auditLogger->log($room, BingoRoomAuditLogger::EVENT_REFUND_COMPLETED, 'system', $message, [
                'lot_id' => (string) $lot->id,
                'amount' => $amount,
            ]);
        } catch (Throwable) {
        }
    }

    private function recordFailure(BingoCardLot $lot, BingoRoom $room, float $amount, string $reason) : void
    {
        BingoLotRefundFailure::updateOrCreate(
            ['lot_id' => $lot->id, 'room_id' => $room->id],
            [
                'user_id' => $lot->user_id,
                'currency_id' => $lot->currency_id,
                'amount' => $amount,
                'error_message' => $reason,
                'status' => 'PENDING',
                'attempts' => \Illuminate\Support\Facades\DB::raw('COALESCE(attempts, 0) + 1'),
                'last_attempted_at' => now(),
            ],
        );
    }

    /**
     * Resuelve el ExternalPlayer comprador real de este lote, si aplica.
     * Un lote vendedor→externo vincula CADA cartón individual a su
     * ExternalPlayer (ver BingoCardGenerationService::generateCards()).
     * El resolver verifica todos los cartones y falla de forma segura si
     * encuentra más de un comprador asociado al mismo lote.
     */
    private function resolveExternalBuyer(BingoCardLot $lot) : ?ExternalPlayer
    {
        return app(ExternalPlayerLotBuyerResolver::class)->resolve($lot);
    }
}
