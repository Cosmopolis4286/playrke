<?php

namespace App\DTOs\Games\Bingo\Room;

use App\DTOs\ContextDTO;
use Illuminate\Support\Collection;

/**
 * DTO para la vista de salas de juego de bingo (player-facing).
 * Distinto del BingoRoomSummaryDTO (admin/commerce CRUD).
 */
final class BingoGameRoomsDTO implements ContextDTO
{
    public function __construct(
        /** @var Collection Salas activas con config y players_room */
        public readonly Collection $rooms,
        /** Tasa de cambio para mostrar prizes en moneda local */
        public readonly float $exchangeRate,
        /** Símbolo de moneda */
        public readonly string $currencySym,
        /** Token del usuario */
        public readonly ?string $userToken,
        /** Datos de fecha/hora del servidor + lista de salas para countdown */
        public readonly ?array $dateTimeRooms,
        /** Precios del cartón para jugadores (por sala) */
        public readonly ?array $cardPrices,
        /** Texto del reglamento del bingo (HTML, configurable por admin) */
        public readonly string $bingoRules = '',
        /** Path relativo de la imagen del reglamento del bingo */
        public readonly string $imageBingoRules = '',
    ) {}

    public function toView(): array
    {
        $roomsWithPrices = $this->rooms->map(function($room) {
            // Mapear relación players → players_room con campo ctns esperado por el frontend
            $playersRoom = $room->relationLoaded('players')
                ? $room->players->map(fn($p) => ['ctns' => (int) $p->cards_purchased])->values()->all()
                : null;

            $roomData = array_merge(
                $room->toArray(),
                [
                    'status_code'  => $room->status?->code ?? 'ACTIVE',
                    'room_type'    => $room->config?->roomType?->code ?? 'PUBLIC',
                    'game_mode'    => $room->config?->gameMode?->code ?? null,
                    'id'           => $room->getRouteKey(), // Usar ID hasheado para route binding
                    'players_room' => $playersRoom,
                ],
            );

            // Agregar precio del cartón si está disponible para esta sala
            if ($this->cardPrices && isset($this->cardPrices[$room->id])) {
                $roomData['card_price'] = $this->cardPrices[$room->id]['card_price'];
                $roomData['currency_symbol'] = $this->cardPrices[$room->id]['currency_symbol'];
            }

            return $roomData;
        })->values()->all();

        return [
            'title'         => 'Salas de Juego',
            'rooms'         => $roomsWithPrices,
            'exchangeRate'  => $this->exchangeRate,
            'currencySym'   => $this->currencySym,
            'userToken'     => $this->userToken,
            'dateTimeRooms' => $this->dateTimeRooms,
            'cardPrices'    => $this->cardPrices,
            'bingoRules'      => $this->bingoRules,
            'imageBingoRules' => $this->imageBingoRules,
        ];
    }
}
