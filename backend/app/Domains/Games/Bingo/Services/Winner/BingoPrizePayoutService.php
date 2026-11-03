<?php

declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Winner;

use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
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
    ) {
    }

    public function payout(BingoCardPrize $cardPrize) : void
    {
        // Los cartones de compradores externos comparten room_user_id con el
        // vendedor. El room user NO identifica al beneficiario económico.
        // Resolver el dueño desde el pago original y retener el premio: no hay
        // wallet para ExternalPlayer ni autorización para acreditar al vendedor.
        $externalPlayerIds = collect();
        if ($cardPrize->external_player_id) {
            $externalPlayerIds->push((string) $cardPrize->external_player_id);
        } elseif ($cardPrize->card_lot_id) {
            $externalPlayerIds = BingoCardPayment::withTrashed()
                ->where('card_lot_id', (string) $cardPrize->card_lot_id)
                ->whereNotNull('external_player_id')
                ->distinct()
                ->pluck('external_player_id')
                ->map(fn ($id) => (string) $id)
                ->values();
        }

        if ($externalPlayerIds->isNotEmpty()) {
            $needsSave = false;
            if ($cardPrize->external_player_id === null && $externalPlayerIds->count() === 1) {
                $cardPrize->external_player_id = $externalPlayerIds->first();
                $needsSave = true;
            }

            if (in_array($cardPrize->prize_status, ['waiting', 'processing'], true)) {
                $cardPrize->prize_status = 'suspended';
                $needsSave = true;
            } elseif ($cardPrize->prize_status === 'claimed') {
                // Premio histórico posiblemente acreditado al vendedor: no
                // revertir fondos automáticamente; requiere conciliación manual.
                $cardPrize->markAsDisputed();
            }

            if ($needsSave) {
                $cardPrize->save();
            }

            $cardPrize->loadMissing('room');
            if ($cardPrize->room) {
                $this->auditLogger->log(
                    $cardPrize->room,
                    BingoRoomAuditLogger::EVENT_PRIZE_PAYMENT_SUSPENDED,
                    'system',
                    'Premio de comprador externo retenido para verificación; no se acredita al vendedor',
                    [
                        'card_prize_id' => (string) $cardPrize->id,
                        'external_player_id' => $cardPrize->external_player_id,
                        'ambiguous_beneficiary' => $externalPlayerIds->count() > 1,
                        'historical_payment_review' => $cardPrize->prize_status === 'disputed',
                    ],
                );
            }

            \Illuminate\Support\Facades\Log::warning('[BingoPrizePayoutService] Premio externo retenido sin acreditar wallet del vendedor', [
                'card_prize_id' => (string) $cardPrize->id,
                'external_player_id' => $cardPrize->external_player_id,
                'external_player_matches' => $externalPlayerIds->count(),
                'prize_status' => $cardPrize->prize_status,
            ]);

            return;
        }

        // Sala DEMO: nunca se acredita (el premio nace 'cancelled' en
        // BingoDrawService; esto cubre reintentos o cambios manuales de estado).
        $cardPrize->loadMissing('room');
        if ($cardPrize->room?->isDemo()) {
            if ($cardPrize->isCancelled() === false) {
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

        if ($cardPrize->room === null) {
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
