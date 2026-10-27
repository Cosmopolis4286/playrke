<?php

namespace App\Domains\Games\Bingo\DTOs\Room\Automatic;

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
        public readonly Collection $connectionTypes,
        public readonly Collection $roomTypes,
        public readonly Collection $gameModeCatalog,
    ) {
    }

    public function toView() : array
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
            // Key 'gameModes' se mantiene por ahora de cara al frontend (Inertia prop);
            // el rename de esta key específica se coordina en un paso aparte.
            // OJO: por esta razón el catálogo real de modos de juego (Solitario,
            // Múltiple, Ranking...) se expone bajo 'gameModeCatalog', NO 'gameModes'.
            'gameModes' => $this->connectionTypes,
            'roomTypes' => $this->roomTypes,
            'gameModeCatalog' => $this->gameModeCatalog,
        ];
    }
}
