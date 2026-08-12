<?php

namespace App\Domains\Games\Bingo\Events;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando una sala entra o sale de la lista de salas
 * disponibles en la vista `game-rooms` (activación/desactivación/cambios
 * de estado o de su configuración pública).
 *
 * Canal: bingo.lobby (público, sin autenticación)
 * Evento: RoomChanged
 *
 * El payload sigue el shape de `BingoGameRoom` (frontend) en valores base
 * (USD), de forma que cada cliente lo combine con su `exchangeRate` y
 * `currencySym` igual que hace con las salas recibidas por props.
 */
final class BingoLobbyRoomChanged implements ShouldBroadcast
{
    use SerializesModels;

    public const ACTION_ACTIVATED   = 'activated';
    public const ACTION_UPDATED     = 'updated';
    public const ACTION_DEACTIVATED = 'deactivated';

    public function __construct(
        public readonly BingoRoom $room,
        /** activated | updated | deactivated */
        public readonly string $action,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('bingo.lobby');
    }

    public function broadcastAs(): string
    {
        return 'RoomChanged';
    }

    public function broadcastWith(): array
    {
        $room = $this->room;
        $config = $room->config;

        return [
            'action' => $this->action,
            'room' => [
                'id'              => $room->getRouteKey(),
                'uuid_code'       => $room->uuid_code,
                'prize_pool'      => (float) $room->prize_pool,
                'total_players'   => (int) $room->total_players,
                'total_cards'     => (int) $room->total_cards,
                'creator_user_id' => (string) $room->creator_user_id,
                'room_type'       => $config?->roomType?->code ?? 'PUBLIC',
                'game_mode'       => $config?->connectionType?->code,
                'status_code'     => $room->status?->code ?? 'ACTIVE',
                'rounds_count'    => $room->rounds()->count(),
                'config' => $config ? [
                    'room_id'            => $config->room_id,
                    'card_price_usd'     => (float) $config->card_price_usd,
                    'prize_pool_type'    => (int) $config->prize_pool_type_id,
                    'unix_date'          => $config->unix_date,
                    'date'               => $config->date?->toDateString(),
                    'time'               => $config->time,
                    'min_cards_to_start' => $config->min_cards_to_start,
                    'max_cards_to_start' => $config->max_cards_to_start,
                    'max_players'        => $config->max_players,
                    'min_players'        => $config->min_players,
                ] : null,
            ],
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
