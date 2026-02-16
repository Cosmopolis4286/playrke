<?php

namespace App\Exceptions\Domain\Games\Bingo\Room;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio relacionadas al agregado Bingo Room
 * en el contexto Admin.
 *
 * Estas excepciones representan reglas de negocio violadas
 * o estados inválidos del comercio.
 *
 * IMPORTANTE:
 * - El dominio expone ERROR CODES, no mensajes humanos
 */
final class BingoRoomException extends DomainException
{
    /**
     * Fallo genérico en la creación de la sala de bingo.
     */
    public static function creationFailed(): self
    {
        return new self('BINGO_ROOM_CREATION_FAILED');
    }

    /**
     * Fallo genérico en la actualización de la sala de bingo.
     */
    public static function updateFailed(): self
    {
        return new self('BINGO_ROOM_UPDATE_FAILED');
    }

    /**
     * Fallo genérico en la eliminación lógica de la sala de bingo.
     */
    public static function deleteFailed(): self
    {
        return new self('BINGO_ROOM_DELETE_FAILED');
    }

    /**
     * La sala de bingo ya se encuentra eliminada.
     */
    public static function alreadyDeleted(): self
    {
        return new self('BINGO_ROOM_ALREADY_DELETED');
    }

    /**
     * Intento de cambiar el estado de una sala de bingo eliminada.
     */
    public static function invalidStatusChange(): self
    {
        return new self('BINGO_ROOM_INVALID_STATUS_CHANGE');
    }

    /**
     * El comercio no existe o no pertenece al contexto actual.
     */
    public static function notFound(): self
    {
        return new self('BINGO_ROOM_NOT_FOUND');
    }

    /**
     * Intento de eliminación física inválida.
     */
    public static function destroyNotAllowed(): self
    {
        return new self('BINGO_ROOM_DESTROY_NOT_ALLOWED');
    }

    public static function invalidRoomType(): self
    {
        return new self('BINGO_ROOM_INVALID_ROOM_TYPE');
    }

    public static function invalidGameMode(): self
    {
        return new self('BINGO_ROOM_INVALID_GAME_MODE');
    }

    public static function invalidPrizePoolType(): self
    {
        return new self('BINGO_ROOM_INVALID_PRIZE_POOL_TYPE');
    }
}
