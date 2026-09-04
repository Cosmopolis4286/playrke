<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Exceptions\Room;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio relacionadas a la sala de Trivia
 * (RoomService, JoinRoomAction, StartGameAction).
 *
 * Cubren la unión de jugadores, capacidad, equipos, cobro de la tarifa de
 * entrada y las validaciones previas a iniciar el juego.
 *
 * IMPORTANTE:
 * - El dominio expone mensajes humanos directos (no ERROR_CODES) porque
 *   son específicos de este flujo y no se repiten en otro dominio.
 */
final class TriviaRoomException extends DomainException
{
    /**
     * Saldo insuficiente en la wallet elegida para cubrir la tarifa de
     * entrada de la sala.
     */
    public static function insufficientBalance(string $symbol, float $chargeAmount) : self
    {
        return new self("Saldo insuficiente. Se requieren {$symbol} {$chargeAmount}.", 422);
    }

    /**
     * El nickname no cumple la longitud mínima/máxima requerida.
     */
    public static function invalidNickname() : self
    {
        return new self('El nickname debe tener entre 2 y 30 caracteres', 422);
    }

    /**
     * El jugador no tiene ninguna wallet activa para pagar la entrada de
     * una sala sin moneda específica configurada (requiere conversión).
     */
    public static function noActiveWallet() : self
    {
        return new self('No tienes una billetera activa para pagar la entrada de esta sala.', 422);
    }

    /**
     * No hay jugadores registrados en la sala al intentar iniciar el juego.
     */
    public static function noPlayersRegistered() : self
    {
        return new self('No hay jugadores registrados en la sala.', 422);
    }

    /**
     * Solo se puede modificar la configuración de una sala mientras está
     * en estado 'waiting' — evita reconstruir una partida ya iniciada.
     */
    public static function notEditableWhileActive() : self
    {
        return new self('No se puede modificar una sala activa', 409);
    }

    /**
     * La sala ya no está en estado 'waiting' al intentar iniciar el juego
     * (ya inició, se canceló o se cerró).
     */
    public static function notInWaitingState(string $currentStatus) : self
    {
        return new self("La sala ya no está en espera (estado: {$currentStatus}).", 409);
    }

    /**
     * La sala no admite nuevas inscripciones en su estado actual
     * (ver RoomDTO::isJoinable()).
     */
    public static function notJoinable() : self
    {
        return new self('No se puede unir a esta sala', 409);
    }

    /**
     * El jugador no tiene una wallet activa en ninguna de las monedas
     * aceptadas por la tarifa de entrada configurada (fee_by_currency).
     */
    public static function noWalletForRequiredCurrency(string $symbols) : self
    {
        return new self("No tienes una billetera en la moneda requerida para esta sala ({$symbols}).", 422);
    }

    /**
     * La sala alcanzó su límite máximo de jugadores.
     */
    public static function roomFull() : self
    {
        return new self('La sala está llena', 409);
    }

    /**
     * Quien solicita iniciar el juego no es el anfitrión de la sala
     * (inicio manual — el inicio automático del sistema no pasa por aquí).
     */
    public static function startForbidden() : self
    {
        return new self('Solo el anfitrión puede iniciar el juego.', 403);
    }

    /**
     * El equipo indicado ya alcanzó su capacidad máxima de jugadores.
     */
    public static function teamFull() : self
    {
        return new self('El equipo seleccionado está lleno', 409);
    }

    /**
     * El equipo indicado no existe en la sala.
     */
    public static function teamNotFound() : self
    {
        return new self('El equipo seleccionado no existe en esta sala', 404);
    }
}
