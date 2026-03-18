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
        /** Snowflake ID del país, mantenido como string para evitar truncado de bigint. */
        public readonly string $countryId,
        public readonly string $playerPhone,
        public readonly string $playerName,
        public readonly string $playerLastName,
        public readonly string $receiptNumber,
        public readonly ?\Illuminate\Http\UploadedFile $receiptImage,
    ) {}

    public static function fromArray(array $data): self
    {
        $player = $data['player'] ?? [];

        return new self(
            roomId:         (string) ($data['room_id'] ?? ''),
            numCtns:        (int)    ($data['num_ctns'] ?? 1),
            countryId:      (string) ($data['country_id'] ?? ''),
            playerPhone:    (string) ($player['phone']     ?? ''),
            playerName:     (string) ($player['first_name'] ?? ''),
            playerLastName: (string) ($player['last_name'] ?? ''),
            receiptNumber:  (string) ($data['receipt_number'] ?? ''),
            receiptImage:   $data['receipt_image'] ?? null,
        );
    }
}
