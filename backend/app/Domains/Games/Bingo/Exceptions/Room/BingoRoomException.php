<?php

namespace App\Domains\Games\Bingo\Exceptions\Room;

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

    public static function expiredRoomStatusLocked(): self
    {
        return new self('No puedes cambiar el estado de una sala cuya fecha/hora ya expiró.');
    }

    public static function roomHasPlayers(): self
    {
        return new self('No puedes eliminar una sala que ya tiene jugadores registrados.');
    }

    /**
     * Intento de activación manual sobre una sala con horario programado.
     */
    public static function activationNotAllowed(): self
    {
        return new self('Solo las salas sin horario programado pueden activarse manualmente.');
    }

    /**
     * El usuario no es el creador de la sala ni tiene rol admin/superadmin.
     */
    public static function activationForbidden(): self
    {
        return new self('No tienes permiso para activar esta sala.');
    }

    /**
     * La sala no está en un estado válido para ser activada manualmente.
     */
    public static function activationInvalidState(): self
    {
        return new self('Solo se pueden activar manualmente salas en estado ACTIVE cuyo sorteo no haya iniciado.');
    }

    /**
     * La fecha/hora indicada para programar el inicio no es válida (pasada o muy próxima).
     */
    public static function scheduleStartInvalidDate(): self
    {
        return new self('La fecha y hora de inicio deben ser al menos 10 minutos posteriores al momento actual.');
    }

    /**
     * La sala tiene configurada una cantidad mínima de cartones a vender
     * y aún no se ha alcanzado, por lo que el sorteo no puede iniciar.
     */
    public static function minCardsNotReached(int $required, int $current): self
    {
        return new self("La sala requiere al menos {$required} cartones vendidos para iniciar (actualmente tiene {$current}).");
    }

    public static function roomFull(): static
    {
        return new static('La sala ha alcanzado el límite máximo de jugadores.');
    }
}
