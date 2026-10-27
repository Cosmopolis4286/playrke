<?php

namespace App\Domains\Games\Bingo\ValueObjects;

/**
 * Reglas de capacidad de una sala de bingo (cartones y jugadores).
 *
 * Única fuente de verdad del backend para los límites de
 * min/max_cards_to_start y min/max_players — el espejo del frontend vive en
 * BINGO_ROOM_INT_LIMITS / BINGO_ROOM_CAPACITY_DEFAULTS (bingoRoomFormSchema.ts).
 *
 * Mecánica que las motiva:
 *  - Un bingo necesita competencia: con un solo jugador siempre gana él y el
 *    bote (menos comisiones) le vuelve, así que el piso de jugadores es 2.
 *    Excepción: el modo Solitario (GameMode SOLO), pensado para jugar solo.
 *  - Cada jugador tiene al menos un cartón → mín. cartones ≥ mín. jugadores.
 *  - Un máximo por debajo de su mínimo hace el mínimo inalcanzable.
 *  - Máx. cartones < máx. jugadores deja parte del cupo de jugadores sin
 *    poder comprar (BingoRoomCardCapacityGuard bloquea la venta al tope).
 *  - Si al vencer el horario no se alcanzó el mínimo (cartones o jugadores),
 *    la sala se cancela y se reembolsa (CheckExpiredBingoRoomsService).
 */
final class BingoRoomCapacityRules
{
    public const GAME_MODE_SOLO = 'SOLO';

    public const MAX_CARDS = 10000;
    public const MAX_PLAYERS = 100000;

    /** Piso de jugadores en modos competitivos (MULTI, RANKING…). */
    public const MIN_PLAYERS_FLOOR = 2;

    /** Piso de jugadores en modo Solitario. */
    public const MIN_PLAYERS_FLOOR_SOLO = 1;

    /**
     * Errores de coherencia entre los 4 límites, como [campo => mensaje].
     * Los nulos (máximos opcionales) se omiten de las comparaciones.
     *
     * @return array<string, string>
     */
    public static function crossErrors(?int $minCards, ?int $maxCards, ?int $minPlayers, ?int $maxPlayers, ?string $gameModeCode) : array
    {
        $errors = [];
        $floor = self::minPlayersFloor($gameModeCode);

        if ($minPlayers !== null && $minPlayers < $floor) {
            $errors['minPlayers'] = $gameModeCode === self::GAME_MODE_SOLO
                ? "El mínimo de jugadores debe ser al menos {$floor}."
                : "Un bingo necesita al menos {$floor} jugadores (solo el modo Solitario permite 1).";
        }

        if ($minCards !== null && $minPlayers !== null && $minCards < $minPlayers) {
            $errors['minCardsToStart'] = 'El mínimo de cartones no puede ser menor al mínimo de jugadores (cada jugador tiene al menos un cartón).';
        }

        if ($minCards !== null && $maxCards !== null && $minCards > $maxCards) {
            $errors['maxCardsToStart'] = 'El máximo de cartones debe ser mayor o igual al mínimo.';
        }

        if ($minPlayers !== null && $maxPlayers !== null && $minPlayers > $maxPlayers) {
            $errors['maxPlayers'] = 'El máximo de jugadores debe ser mayor o igual al mínimo.';
        }

        if (! isset($errors['maxCardsToStart']) && $maxCards !== null && $maxPlayers !== null && $maxCards < $maxPlayers) {
            $errors['maxCardsToStart'] = 'El máximo de cartones no puede ser menor al máximo de jugadores: parte del cupo no podría comprar.';
        }

        return $errors;
    }

    public static function minPlayersFloor(?string $gameModeCode) : int
    {
        return $gameModeCode === self::GAME_MODE_SOLO
            ? self::MIN_PLAYERS_FLOOR_SOLO
            : self::MIN_PLAYERS_FLOOR;
    }
}
