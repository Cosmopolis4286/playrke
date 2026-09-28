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
 * - El dominio expone ERROR CODES, no mensajes humanos. Los métodos con
 *   datos dinámicos (ej. minCardsNotReached()) pasan placeholders vía el
 *   4º argumento de DomainException — Handler::resolveMessage() los
 *   inyecta en la traducción con sintaxis `:nombre` (ver lang/*\/errors.php).
 */
final class BingoRoomException extends DomainException
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
     * Intento de comprar/generar cartones en una sala privada sin haber
     * verificado antes el código de acceso y la contraseña.
     */
    public static function privateRoomNotVerified(): self
    {
        return new self('BINGO_ROOM_PRIVATE_NOT_VERIFIED', 403);
    }

    /**
     * Intento de eliminación física de una sala con historial de actividad
     * (jugadores, cartones vendidos o bote acumulado) — el hard delete
     * cascadea hacia tablas financieras (premios, pagos, comisiones,
     * reembolsos) y destruiría esa evidencia de forma irreversible.
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

    public static function expiredRoomStatusLocked(): self
    {
        return new self('BINGO_ROOM_EXPIRED_STATUS_LOCKED', 409);
    }

    public static function roomHasPlayers(): self
    {
        return new self('BINGO_ROOM_HAS_PLAYERS', 409);
    }

    /**
     * Intento de activación manual sobre una sala con horario programado.
     */
    public static function activationNotAllowed(): self
    {
        return new self('BINGO_ROOM_ACTIVATION_NOT_ALLOWED', 422);
    }

    /**
     * El usuario no es el creador de la sala ni tiene rol admin/superadmin.
     */
    public static function activationForbidden(): self
    {
        return new self('BINGO_ROOM_ACTIVATION_FORBIDDEN', 403);
    }

    /**
     * La sala no está en un estado válido para ser activada manualmente.
     */
    public static function activationInvalidState(): self
    {
        return new self('BINGO_ROOM_ACTIVATION_INVALID_STATE', 409);
    }

    /**
     * La fecha/hora indicada para programar el inicio no es válida (pasada o muy próxima).
     */
    public static function scheduleStartInvalidDate(): self
    {
        return new self('BINGO_ROOM_SCHEDULE_START_INVALID_DATE', 422);
    }

    /**
     * La sala tiene configurada una cantidad mínima de cartones a vender
     * y aún no se ha alcanzado, por lo que el sorteo no puede iniciar.
     */
    public static function minCardsNotReached(int $required, int $current): self
    {
        return new self('BINGO_ROOM_MIN_CARDS_NOT_REACHED', 422, replacements: [
            'required' => $required,
            'current'  => $current,
        ]);
    }

    public static function minPlayersNotReached(int $required, int $current): self
    {
        return new self('BINGO_ROOM_MIN_PLAYERS_NOT_REACHED', 422, replacements: [
            'required' => $required,
            'current'  => $current,
        ]);
    }

    public static function roomFull(): static
    {
        return new static('BINGO_ROOM_FULL', 409);
    }

    /**
     * La compra solicitada excedería el tope de cartones configurado para la
     * sala (max_cards_to_start) — ver BingoRoomCardCapacityGuard.
     */
    public static function cardLimitExceeded(int $remaining, int $requested): static
    {
        if ($remaining > 0) {
            return new static('BINGO_ROOM_CARD_LIMIT_PARTIAL', 409, replacements: [
                'remaining' => $remaining,
                'requested' => $requested,
            ]);
        }

        return new static('BINGO_ROOM_CARD_LIMIT_EXHAUSTED', 409);
    }

    /**
     * Intento de quitar de "monedas permitidas" una moneda que ya tiene
     * cartones vendidos en la sala (dejaría ventas existentes huérfanas).
     */
    public static function currencyRemovalWithSalesNotAllowed(string $currencyLabels): self
    {
        return new self('BINGO_ROOM_CURRENCY_REMOVAL_WITH_SALES_NOT_ALLOWED', 409, replacements: [
            'currencies' => $currencyLabels,
        ]);
    }

    /**
     * Intento de editar una sala cuyo sorteo ya inició o terminó
     * (IN_PROGRESS, FINISHED, CANCELLED) — a partir de ahí puede haber
     * premios entregados que se perderían al reconstruir las rondas.
     */
    public static function roomNotEditableStatus(): self
    {
        return new self('BINGO_ROOM_NOT_EDITABLE_STATUS', 409);
    }

    /**
     * Con cartones ya vendidos no se puede cambiar lo que el jugador "compró":
     * bote, rondas y premios, seriales, variante ni precio del cartón.
     *
     * @param string[] $fields Etiquetas legibles de los campos que se intentó cambiar.
     */
    public static function fieldsLockedBySales(array $fields): self
    {
        return new self('BINGO_ROOM_FIELDS_LOCKED_BY_SALES', 409, replacements: [
            'fields' => implode(', ', $fields),
        ]);
    }
}
