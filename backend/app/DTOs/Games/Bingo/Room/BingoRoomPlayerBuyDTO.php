<?php

namespace App\DTOs\Games\Bingo\Room;

/**
 * DTO inmutable para la compra de cartones desde la vista game-rooms.
 * El jugador compra para sí mismo — sus datos vienen de la sesión/Auth.
 */
final class BingoRoomPlayerBuyDTO
{
    public function __construct(
        /** ID real de la sala (decodificado por route model binding) */
        public readonly string $roomId,
        /** Cantidad de cartones a comprar */
        public readonly int    $numCtns,
        /** Snowflake ID del país seleccionado */
        public readonly string $countryId,
    ) {}

    public static function fromArray(array $data, string $roomId): self
    {
        return new self(
            roomId:    $roomId,
            numCtns:   (int)    ($data['num_ctns']   ?? 1),
            countryId: (string) ($data['country_id'] ?? ''),
        );
    }
}
