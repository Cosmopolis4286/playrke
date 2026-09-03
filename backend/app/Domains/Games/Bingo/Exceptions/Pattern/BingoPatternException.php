<?php

namespace App\Domains\Games\Bingo\Exceptions\Pattern;

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
final class BingoPatternException extends DomainException
{
    /**
     * Fallo genérico en la creación de la sala de bingo.
     */
    public static function creationFailed(): self
    {
        return new self('BINGO_ROOM_CREATION_FAILED', 422);
    }

    /**
     * Fallo genérico en la actualización de la sala de bingo.
     */
    public static function updateFailed(): self
    {
        return new self('BINGO_ROOM_UPDATE_FAILED', 422);
    }

    /**
     * Fallo genérico en la eliminación lógica de la sala de bingo.
     */
    public static function deleteFailed(): self
    {
        return new self('BINGO_ROOM_DELETE_FAILED', 422);
    }

    /**
     * La sala de bingo ya se encuentra eliminada.
     */
    public static function alreadyDeleted(): self
    {
        return new self('BINGO_ROOM_ALREADY_DELETED', 409);
    }

    /**
     * Intento de cambiar el estado de una sala de bingo eliminada.
     */
    public static function invalidStatusChange(): self
    {
        return new self('BINGO_ROOM_INVALID_STATUS_CHANGE', 409);
    }

    /**
     * El comercio no existe o no pertenece al contexto actual.
     */
    public static function notFound(): self
    {
        return new self('BINGO_ROOM_NOT_FOUND', 404);
    }

    /**
     * Intento de eliminación física inválida.
     */
    public static function destroyNotAllowed(): self
    {
        return new self('BINGO_ROOM_DESTROY_NOT_ALLOWED', 409);
    }

    public static function invalidRoomType(): self
    {
        return new self('BINGO_ROOM_INVALID_ROOM_TYPE', 422);
    }

    public static function invalidGameMode(): self
    {
        return new self('BINGO_ROOM_INVALID_GAME_MODE', 422);
    }

    public static function invalidPrizePoolType(): self
    {
        return new self('BINGO_ROOM_INVALID_PRIZE_POOL_TYPE', 422);
    }

    /**
     * Ya existe un patrón con ese nombre para el usuario.
     */
    public static function duplicateName(): self
    {
        return new self('BINGO_PATTERN_DUPLICATE_NAME', 409);
    }
}
