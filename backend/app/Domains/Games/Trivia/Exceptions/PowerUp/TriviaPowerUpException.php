<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Exceptions\PowerUp;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio relacionadas al consumo de power-ups de Trivia
 * (TriviaPowerUpService).
 *
 * IMPORTANTE:
 * - El dominio expone mensajes humanos directos (no ERROR_CODES) porque
 *   son específicos de este flujo y no se repiten en otro dominio.
 */
final class TriviaPowerUpException extends DomainException
{
    /**
     * El usuario no tiene un registro de participante activo en la sala
     * indicada (no está inscrito).
     */
    public static function notEnrolled() : self
    {
        return new self('No estás inscrito en esta sala.', 403);
    }

    /**
     * Se agotó tanto el uso gratis por partida (power_ups_used) como el
     * fallback de Marketplace (TRIVIA_HINT_TICKET) para este power-up.
     */
    public static function noUsesAvailable() : self
    {
        return new self('Ya no tienes usos disponibles de este power-up.', 409);
    }
}
