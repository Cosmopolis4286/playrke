<?php

namespace App\DTOs\Games\Bingo\Room;

use App\DTOs\ContextDTO;

use Illuminate\Support\Collection;

final class BingoRoomSummaryDTO implements ContextDTO
{
    public function __construct(
        public readonly array $rooms,
        public readonly ?array $pagination,
        public readonly Collection $countries,
        public readonly Collection $currencies,
        public readonly Collection $games,
        public readonly Collection $timeZones,
        public readonly Collection $bingoPatterns,
        public readonly Collection $typesPrizePool,
        public readonly Collection $gameModes,
    ) {}

    public function toView(): array
    {
        return [
            'title'                 => 'Salas de Juego',
            'rooms'             => $this->rooms,
            'rooms_pagination'  => $this->pagination,
            'countries'             => $this->countries,
            'currencies'            => $this->currencies,
            'games'                 => $this->games,
            'timeZones'             => $this->timeZones,
            'bingoPatterns'         => $this->bingoPatterns,
            'typesPrizePool'        => $this->typesPrizePool,
            'gameModes'             => $this->gameModes,
        ];
    }
}
