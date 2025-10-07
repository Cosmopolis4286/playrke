<?php

namespace App\Enums\Games;

use App\Models\GameStatus as GameStatusModel;
use Illuminate\Support\Collection;

/**
 * Enum para los estados de juego, con soporte de carga dinámica desde la DB.
 */
enum GameStatus: string
{
    // Valores genéricos temporales (para autocompletado)
    case NONE    = 'NONE';
    case DRAFT   = 'DRAFT';
    case ACTIVE  = 'ACTIVE';
    case IN_PROGRESS = 'IN_PROGRESS';
    case FINISHED = 'FINISHED';
    case CLOSED  = 'CLOSED';

    /**
     * Retorna una etiqueta legible para UI.
     *
     * Si existe en la DB, retorna la descripción de la tabla `game_status`.
     */
    public function label(): string
    {
        $status = GameStatusModel::where('value', $this->value)->first();
        return $status->description ?? match ($this) {
            self::NONE => 'N/D',
            self::DRAFT => 'Borrador',
            self::ACTIVE => 'Activa',
            self::IN_PROGRESS => 'En Progreso',
            self::FINISHED => 'Finalizada',
            self::CLOSED => 'Cerrada',
        };
    }

    /**
     * Retorna todos los estados disponibles desde la DB como una colección de enums.
     */
    public static function allFromDb(): Collection
    {
        return GameStatusModel::all()
            ->map(fn($row) => self::tryFrom($row->value))
            ->filter(fn($enum) => $enum !== null);
    }

    /**
     * Determina si el estado representa una ronda finalizada.
     */
    public function isFinished(): bool
    {
        return in_array($this, [self::FINISHED, self::CLOSED], true);
    }

    /**
     * Retorna el enum a partir del valor de la DB o null si no existe.
     */
    public static function fromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
