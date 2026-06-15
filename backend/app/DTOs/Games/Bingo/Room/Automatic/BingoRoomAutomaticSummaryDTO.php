<?php

namespace App\DTOs\Games\Bingo\Room\Automatic;

use App\DTOs\ContextDTO;

use Illuminate\Support\Collection;

final class BingoRoomAutomaticSummaryDTO implements ContextDTO
{
    public function __construct(
        public readonly array $rooms,
        public readonly ?array $pagination,
        public readonly Collection|array $currencies,
        public readonly array $currenciesWithActiveRate,
        public readonly Collection $bingoPatterns,
        public readonly Collection $prizePoolTypes,
        public readonly Collection $gameModes,
        public readonly Collection $roomTypes,
    ) {}

    public function toView(): array
    {
        return [
            'title' => 'Salas de Bingo',
            'subtitle' => 'Creación Automática',
            'rooms' => $this->rooms,
            'rooms_pagination' => $this->pagination,
            'currencies' => $this->currencies,
            'currenciesWithActiveRate' => $this->currenciesWithActiveRate,
            'bingoPatterns' => $this->bingoPatterns,
            'prizePoolTypes' => $this->prizePoolTypes,
            'gameModes' => $this->gameModes,
            'roomTypes' => $this->roomTypes,
        ];
    }
}
