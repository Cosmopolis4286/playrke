<?php

namespace App\Events\Games\Bingo;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando el estado público de una sala cambia.
 *
 * Escenarios que lo disparan:
 *  - Generación de cartones (prize_pool, total_players y total_cards cambian)
 *
 * Canal: bingo.room.{roomId} (público, sin autenticación, roomId = ID Snowflake)
 * Evento: RoomUpdated
 *
 * Payload recibido en el cliente:
 * {
 *   room_id:        string  — ID Snowflake de la sala (coincide con BingoGameRoom.id)
 *   prize_pool:     float   — Bote acumulado actualizado
 *   total_players:  int     — Total de jugadores en la sala
 *   total_cards:    int     — Total de cartones vendidos en la sala
 *   card_price_usd: float   — Precio base del cartón en USD
 * }
 */
final class BingoRoomUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(
        /** ID Snowflake de la sala (coincide con BingoGameRoom.id en el frontend) */
        public readonly string $roomId,
        /** Bote acumulado actualizado */
        public readonly float  $prizePool,
        /** Total de jugadores en la sala */
        public readonly int    $totalPlayers,
        /** Precio base del cartón en USD */
        public readonly float  $cardPriceUsd,
        /** Total de cartones vendidos en la sala */
        public readonly int    $totalCards = 0,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("bingo.room.{$this->roomId}");
    }

    public function broadcastAs(): string
    {
        return 'RoomUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id'        => $this->roomId,
            'prize_pool'     => $this->prizePool,
            'total_players'  => $this->totalPlayers,
            'total_cards'    => $this->totalCards,
            'card_price_usd' => $this->cardPriceUsd,
        ];
    }

    /**
     * Forzar transmisión inmediata sin cola
     */
    public function broadcastConnection()
    {
        return 'reverb';
    }
}
