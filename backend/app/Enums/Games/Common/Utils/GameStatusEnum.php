<?php

namespace App\Enums\Games\Common\Utils;

use App\Models\Games\Common\Utils\GameStatus;
use Illuminate\Support\Collection;

/**
 * Enum para los estados de juego, con soporte de carga dinámica desde la DB.
 */
enum GameStatusEnum : string
{
    /**
     * Retorna todos los estados disponibles desde la DB como una colección de enums.
     */
    public static function allFromDb() : Collection
    {
        return GameStatus::all()
            ->map(fn ($row) => self::tryFrom($row->id))
            ->filter(fn ($enum) => $enum !== null);
    }

    /**
     * Retorna el enum a partir del valor de la DB o null si no existe.
     */
    public static function fromValue(string $id) : ?self
    {
        return self::tryFrom($id);
    }

    /**
     * Determina si el estado representa una ronda finalizada.
     */
    public function isFinished() : bool
    {
        return in_array($this, [self::FINISHED, self::CLOSED], true);
    }

    /**
     * Retorna una etiqueta legible para UI.
     *
     * Si existe en la DB, retorna la descripción de la tabla `game_status`.
     */
    public function label() : string
    {
        $status = GameStatus::where('id', $this->id)->first();

        return $status->label ?? match ($this) {
            self::INACTIVE => 'Inactiva',
            self::ACTIVE => 'Activa',
            self::IN_PROGRESS => 'En Progreso',
            self::FINISHED => 'Finalizada',
            self::CLOSED => 'Cerrada',
        };
    }
    case ACTIVE = 'ACTIVE';
    case CLOSED = 'CLOSED';
    case FINISHED = 'FINISHED';
    case IN_PROGRESS = 'IN_PROGRESS';
    // Valores genéricos temporales (para autocompletado)
    case INACTIVE = 'INACTIVE';
}
