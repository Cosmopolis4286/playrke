<?php

declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Winner;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
use App\Services\Games\GamePrizeService;

/**
 * Adaptador bingo → GamePrizeService.
 *
 * BingoPrizePayoutService delega toda la lógica de pago atómico, idempotente
 * y recuperable a GamePrizeService (fuente de verdad cross-game).
 *
 * La descripción del movimiento de wallet se construye aquí porque es
 * bingo-específica; el resto de la FSM, el lockForUpdate y el registro del
 * pago ocurren en GamePrizeService.
 *
 * Tras el payout, esta clase también deja constancia en bingo_room_audits del
 * resultado (completed/suspended/failed) — GamePrizeService es cross-game y no
 * conoce bingo_room_audits, así que ese registro vive aquí, no allá.
 */
final class BingoPrizePayoutService
{
    public function __construct(
        private readonly GamePrizeService $gamePrizeService,
        private readonly BingoRoomAuditLogger $auditLogger,
    ) {}

    public function payout(BingoCardPrize $cardPrize): void
    {
        // Sala DEMO: nunca se acredita (el premio nace 'cancelled' en
        // BingoDrawService; esto cubre reintentos o cambios manuales de estado).
        $cardPrize->loadMissing('room');
        if ($cardPrize->room?->isDemo()) {
            if (!$cardPrize->isCancelled()) {
                $cardPrize->update(['prize_status' => 'cancelled']);
            }
            return;
        }

        $this->gamePrizeService->payout(
            prizeRecord: $cardPrize,
            description: "Premio Bingo — Sala #{$cardPrize->room_id}",
        );

        $cardPrize->refresh();
        $cardPrize->loadMissing('room');

        if (!$cardPrize->room) {
            return;
        }

        match ($cardPrize->prize_status) {
            'claimed' => $this->auditLogger->log(
                $cardPrize->room,
                BingoRoomAuditLogger::EVENT_PRIZE_PAYMENT_COMPLETED,
                'system',
                'Premio acreditado a la billetera del ganador',
                ['card_prize_id' => (string) $cardPrize->id, 'usd_amount' => $cardPrize->usd_amount],
            ),
            'suspended' => $this->auditLogger->log(
                $cardPrize->room,
                BingoRoomAuditLogger::EVENT_PRIZE_PAYMENT_SUSPENDED,
                'system',
                'Premio suspendido para revisión manual — ganador sin billetera activa',
                ['card_prize_id' => (string) $cardPrize->id, 'usd_amount' => $cardPrize->usd_amount],
            ),
            'processing' => $this->auditLogger->log(
                $cardPrize->room,
                BingoRoomAuditLogger::EVENT_PRIZE_PAYMENT_FAILED,
                'system',
                'Pago de premio falló — queda pendiente de reintento',
                ['card_prize_id' => (string) $cardPrize->id, 'usd_amount' => $cardPrize->usd_amount],
            ),
            default => null,
        };
    }
}
