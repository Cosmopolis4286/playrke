<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Exceptions\Pause;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio relacionadas a la pausa/reanudación de una
 * partida de Trivia (TriviaPauseService).
 *
 * Ambas variantes también se capturan internamente (maybeAutoPause()/
 * maybeAutoResume()) para ignorar en silencio el caso "ya estaba en el
 * estado esperado" al evaluar la pausa/reanudación automática por
 * desconexión masiva.
 *
 * IMPORTANTE:
 * - El dominio expone mensajes humanos directos (no ERROR_CODES) porque
 *   son específicos de este flujo y no se repiten en otro dominio.
 */
final class TriviaPauseException extends DomainException
{
    /**
     * Se intentó pausar una partida que no está en estado 'active'.
     */
    public static function notActive() : self
    {
        return new self('La partida no está activa.', 409);
    }

    /**
     * Se intentó reanudar una partida que no está en estado 'paused'.
     */
    public static function notPaused() : self
    {
        return new self('La partida no está pausada.', 409);
    }
}
