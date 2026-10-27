<?php

declare(strict_types=1);

namespace App\Domains\Games\Bingo\StateMachine;

use App\Domains\Games\Bingo\Exceptions\Room\BingoRoomException;

/**
 * Máquina de estados para salas de bingo.
 *
 * Estados válidos y sus transiciones permitidas:
 *
 *   INACTIVE ──► ACTIVE ──► IN_PROGRESS ──► FINISHED
 *      │            │                            │
 *      │            └──────────────► CANCELLED ◄─┘
 *      │
 *      └──────────────────────────► DELETED
 */
final class BingoRoomStateMachine
{
    private const TRANSITIONS = [
        'INACTIVE' => ['ACTIVE', 'DELETED'],
        'ACTIVE' => ['INACTIVE', 'IN_PROGRESS', 'CANCELLED'],
        'IN_PROGRESS' => ['FINISHED', 'CANCELLED'],
        'FINISHED' => [],
        'CANCELLED' => [],
        'DELETED' => [],
    ];

    /**
     * Valida la transición y lanza BingoRoomException si no es válida.
     */
    public static function assertTransition(string $from, string $to) : void
    {
        if (! self::canTransitionTo($from, $to)) {
            throw BingoRoomException::invalidStatusChange();
        }
    }

    public static function canTransitionTo(string $from, string $to) : bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function isTerminal(string $state) : bool
    {
        return self::TRANSITIONS[$state] === [];
    }

    public static function validStates() : array
    {
        return array_keys(self::TRANSITIONS);
    }
}
