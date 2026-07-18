<?php

namespace App\Enums\Games\Common;

/**
 * Slug de juego, usado como identificador games-agnostic en servicios
 * compartidos entre juegos (ej. AntiCheatService, GamePrizeService).
 */
enum GameSlug: string
{
    case BINGO  = 'bingo';
    case TRIVIA = 'trivia';

    public function label(): string
    {
        return match ($this) {
            self::BINGO  => 'Bingo',
            self::TRIVIA => 'Trivia',
        };
    }
}
