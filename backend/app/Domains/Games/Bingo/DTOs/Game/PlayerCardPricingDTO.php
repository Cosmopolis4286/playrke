<?php

namespace App\Domains\Games\Bingo\DTOs\Game;

/**
 * Precio del cartón y datos de moneda resueltos para un jugador específico,
 * según la wallet activa que tenga configurada (si tiene alguna).
 */
final class PlayerCardPricingDTO
{
    public function __construct(
        public readonly string $currencySymbol,
        public readonly ?int $currencyId,
        public readonly float $exchangeRate,
        public readonly float $cardPrice,
    ) {
    }
}
