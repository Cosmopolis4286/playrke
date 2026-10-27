<?php

namespace App\Domains\Games\Bingo\DTOs\Card\Generate;

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
        /** false si el vendedor (o su comercio/agencia) no está ACTIVE — la vista muestra el aviso en vez del formulario. */
        public readonly bool $canSell = true,
        public readonly ?string $blockedMessage = null,
    ) {
    }

    public function toView() : array
    {
        return [
            'title' => 'Generar Cartones',
            'gameModes' => $this->gameModes,
            'roomTypes' => $this->roomTypes,
            'prizePoolTypes' => $this->prizePoolTypes,
            'canSell' => $this->canSell,
            'blockedMessage' => $this->blockedMessage,
        ];
    }
}
