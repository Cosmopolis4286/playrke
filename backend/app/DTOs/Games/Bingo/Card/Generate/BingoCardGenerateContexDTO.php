<?php

namespace App\DTOs\Games\Bingo\Card\Generate;

use App\DTOs\ContextDTO;
use Illuminate\Support\Collection;

/**
 * DTO inmutable para el contexto de la generación de cartones de Bingo por vendedor.
 */
final class BingoCardGenerateContexDTO implements ContextDTO
{
    public function __construct(
        public readonly Collection $gameModes,
        public readonly Collection $roomTypes,
        public readonly Collection $prizePoolTypes,
    ) {}

    public function toView(): array
    {
        return [
            'title'          => 'Generar Cartones',
            'gameModes'      => $this->gameModes,
            'roomTypes'      => $this->roomTypes,
            'prizePoolTypes' => $this->prizePoolTypes,
        ];
    }
}
