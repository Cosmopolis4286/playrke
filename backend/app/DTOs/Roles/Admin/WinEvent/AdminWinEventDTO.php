<?php

namespace App\DTOs\Roles\Admin\WinEvent;

/**
 * DTO para el registro manual de un evento de ganancia desde el panel admin.
 */
final class AdminWinEventDTO
{
    public static function fromArray(array $data) : self
    {
        return new self(
            $data['user_id'] ?? null,
            $data['winner_name'] ?? null,
            $data['game_slug'],
            $data['game_name'],
            $data['prize_kind'],
            $data['prize_label'] ?? null,
            isset($data['amount_usd']) ? (float) $data['amount_usd'] : null,
            isset($data['amount_local']) ? (float) $data['amount_local'] : null,
            $data['currency_id'] ?? null,
            $data['notes'] ?? null,
        );
    }

    public function __construct(
        public readonly ?string $userId,
        public readonly ?string $winnerName,
        public readonly string $gameSlug,
        public readonly string $gameName,
        public readonly string $prizeKind,
        public readonly ?string $prizeLabel,
        public readonly ?float $amountUsd,
        public readonly ?float $amountLocal,
        public readonly ?string $currencyId,
        public readonly ?string $notes,
    ) {
    }
}
