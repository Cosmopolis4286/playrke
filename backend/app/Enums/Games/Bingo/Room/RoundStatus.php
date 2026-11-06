<?php

namespace App\Enums\Games\Bingo\Room;

/**
 * Enum que representa los estados posibles de una ronda de bingo.
 */
enum RoundStatus : string
{
    /**
     * Verifica si la ronda está activa.
     */
    public function isActive() : bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Verifica si la ronda está en estado inicial.
     */
    public function isDraft() : bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Verifica si la ronda ya finalizó.
     */
    public function isFinished() : bool
    {
        return in_array($this, [self::FINISHED, self::CLOSED], true);
    }

    /**
     * Verifica si la ronda está en progreso.
     */
    public function isInProgress() : bool
    {
        return $this === self::IN_PROGRESS;
    }

    /**
     * Retorna una etiqueta legible para mostrar en UI.
     */
    public function label() : string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::ACTIVE => 'Activa',
            self::IN_PROGRESS => 'En Progreso',
            self::FINISHED => 'Finalizada',
            self::CLOSED => 'Cerrada',
        };
    }
    case ACTIVE = 'ACTIVE';       // Ronda activa y lista para jugar
    case CLOSED = 'CLOSED';       // Ronda cerrada, ya no jugable
    case DRAFT = 'DRAFT';        // Estado inicial, aún no comienza la ronda
    case FINISHED = 'FINISHED';     // Ronda finalizada
    case IN_PROGRESS = 'IN_PROGRESS';  // Ronda en curso
}
