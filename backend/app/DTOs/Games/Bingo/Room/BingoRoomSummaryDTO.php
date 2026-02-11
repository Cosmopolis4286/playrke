<?php

namespace App\DTOs\Games\Bingo\Room;

use App\DTOs\ContextDTO;

use Illuminate\Support\Collection;

final class BingoRoomSummaryDTO implements ContextDTO
{
    public function __construct(
        public readonly ?string $subtitle = '',
        public readonly array $rooms,
        public readonly ?array $pagination,
        public readonly Collection $countries,
        public readonly Collection $currencies,
        public readonly Collection $games,
        public readonly Collection $timeZones,
    ) {}

    public function toView(): array
    {
        return [
            'title'                 => 'Salas de Juego',
            'subtitle'              => $this->subtitle,
            'rooms'             => $this->rooms,
            'rooms_pagination'  => $this->pagination,
            'countries'             => $this->countries,
            'currencies'            => $this->currencies,
            'games'                 => $this->games,
            'timeZones'             => $this->timeZones,
        ];
    }
}
