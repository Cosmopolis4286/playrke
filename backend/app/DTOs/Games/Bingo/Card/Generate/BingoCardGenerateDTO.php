<?php

namespace App\DTOs\Games\Bingo\Card\Generate;

/**
 * DTO inmutable para la generación de cartones de Bingo por vendedor.
 */
final class BingoCardGenerateDTO
{
    public function __construct(
        public readonly string $roomId,
        public readonly int    $numCtns,
        public readonly int    $countryId,
        public readonly string $playerPhone,
        public readonly string $playerName,
        public readonly string $playerLastName,
    ) {}

    public static function fromArray(array $data): self
    {
        $player = $data['player'] ?? [];

        return new self(
            roomId:         (string) ($data['room_id'] ?? ''),
            numCtns:        (int)    ($data['num_ctns'] ?? 1),
            countryId:      (int)    ($data['country_id'] ?? 0),
            playerPhone:    (string) ($player['phone']     ?? ''),
            playerName:     (string) ($player['name']      ?? ''),
            playerLastName: (string) ($player['last_name'] ?? ''),
        );
    }
}
