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
        /** Moneda activa */
        public readonly ?string $currency,
        /** Datos del creador de la sala */
        public readonly ?array $creator,
        /** Identificador del jugador */
        public readonly ?string $player,
        /** Token del usuario */
        public readonly ?string $userToken,
        /** ID encriptado del cliente */
        public readonly ?string $customerId,
        /** Usuario creador (para rutas de fecha/hora) */
        public readonly ?string $creatorUser,
        /** Datos de fecha/hora del servidor + lista de salas para countdown */
        public readonly ?array $dateTimeRooms,
    ) {}

    public function toView(): array
    {
        return [
            'title'         => 'Salas de Juego',
            'rooms'         => $this->rooms->values()->all(),
            'exchangeRate'  => $this->exchangeRate,
            'currencySym'   => $this->currencySym,
            'currency'      => $this->currency,
            'creator'       => $this->creator,
            'player'        => $this->player,
            'userToken'     => $this->userToken,
            'customerId'    => $this->customerId,
            'creatorUser'   => $this->creatorUser,
            'dateTimeRooms' => $this->dateTimeRooms,
        ];
    }
}
