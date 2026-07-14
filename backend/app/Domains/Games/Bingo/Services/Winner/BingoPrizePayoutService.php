<?php

declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Winner;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
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
 */
final class BingoPrizePayoutService
{
    public function __construct(
        private readonly GamePrizeService $gamePrizeService,
    ) {}

    public function payout(BingoCardPrize $cardPrize): void
    {
        $this->gamePrizeService->payout(
            prizeRecord: $cardPrize,
            description: "Premio Bingo — Sala #{$cardPrize->room_id}",
        );
    }
}
