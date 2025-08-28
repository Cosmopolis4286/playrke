<?php

namespace App\Enums\Games\Bingo;

/**
 * Enum que representa los estados posibles de una ronda de bingo.
 *
 * @package App\Enums\Games\Bingo
 */
enum RoundStatus: string
{
    case DRAFT       = 'DRAFT';        // Estado inicial, aún no comienza la ronda
    case ACTIVE      = 'ACTIVE';       // Ronda activa y lista para jugar
    case IN_PROGRESS = 'IN_PROGRESS';  // Ronda en curso
    case FINISHED   = 'FINISHED';      // Ronda finalizada
    case CLOSED      = 'CLOSED';       // Ronda cerrada, ya no jugable

    /**
     * Retorna una etiqueta legible para el estado de la ronda.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT       => 'Borrador',
            self::ACTIVE      => 'Activa',
            self::IN_PROGRESS => 'En Progreso',
            self::FINISHED   =>  'Finalizada',
            self::CLOSED      => 'Cerrada',
        };
    }

    /**
     * Determina si el estado representa una ronda finalizada.
     *
     * @return bool
     */
    public function isFinished(): bool
    {
        return in_array($this, [self::FINISHED, self::CLOSED], true);
    }
}
