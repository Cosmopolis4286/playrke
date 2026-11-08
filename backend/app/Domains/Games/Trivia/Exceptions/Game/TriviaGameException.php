<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Exceptions\Game;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio relacionadas al motor de juego de Trivia
 * (GameEngineService, SubmitAnswerAction, FinishGameAction).
 *
 * Cubren el ciclo de vida de una partida: inicio, estado activo/pausado,
 * envío y validación de respuestas, y finalización.
 *
 * IMPORTANTE:
 * - El dominio expone mensajes humanos directos (no ERROR_CODES) porque
 *   son específicos de este flujo y no se repiten en otro dominio.
 */
final class TriviaGameException extends DomainException
{
    /**
     * El jugador ya envió una respuesta para la pregunta activa —
     * protegido también por unique(game_id,player_id,question_id) en BD,
     * este chequeo evita el side-effect de sumar puntos antes de fallar.
     */
    public static function alreadyAnswered() : self
    {
        return new self('Ya respondiste esta pregunta', 409);
    }

    /**
     * Quien solicita finalizar el juego no es el anfitrión de la sala ni
     * el propio sistema (finalización automática).
     */
    public static function finishForbidden() : self
    {
        return new self('Sin permisos para finalizar el juego', 403);
    }

    /**
     * El juego no está en estado activo (ya finalizó, fue cancelado o aún
     * no inició) al intentar operar sobre él — GameEngineService::submitAnswer().
     */
    public static function gameNotActive() : self
    {
        return new self('Juego no activo', 409);
    }

    /**
     * Variante de gameNotActive() usada por SubmitAnswerAction (capa Action,
     * validación previa a delegar en GameEngineService) — mismo concepto,
     * texto ligeramente distinto porque así existía en el código original y
     * ambos son visibles al usuario final.
     */
    public static function gameNotActiveForAnswer() : self
    {
        return new self('El juego no está activo', 409);
    }

    /**
     * El juego no existe o su estado no fue encontrado en el repositorio
     * (Redis/BD) — GameEngineService::finishGame()/getGameState().
     */
    public static function gameNotFound() : self
    {
        return new self('Juego no encontrado', 404);
    }

    /**
     * El banco no tiene preguntas activas suficientes para la sala: la sala
     * pasa a 'cancelling' y se reembolsa la entrada (StartGameAction).
     */
    public static function insufficientQuestions(int $available, int $required) : self
    {
        return new self("No hay preguntas suficientes para iniciar la partida ({$available} de {$required}). La sala se cancela y se reembolsa la entrada.", 409);
    }

    /**
     * La opción enviada no forma parte de las opciones válidas de la
     * pregunta activa.
     */
    public static function invalidAnswerOption() : self
    {
        return new self('Respuesta no válida', 422);
    }

    /**
     * No hay una pregunta activa en este momento para el juego indicado —
     * GameEngineService::submitAnswer().
     */
    public static function noActiveQuestion() : self
    {
        return new self('No hay pregunta activa', 422);
    }

    /**
     * Variante de noActiveQuestion() usada por SubmitAnswerAction (capa
     * Action, validación previa a delegar en GameEngineService).
     */
    public static function noActiveQuestionForAnswer() : self
    {
        return new self('No hay pregunta activa en este momento', 422);
    }

    /**
     * El tiempo permitido para responder la pregunta activa ya expiró.
     */
    public static function responseTimeExceeded() : self
    {
        return new self('Tiempo de respuesta excedido', 422);
    }

    /**
     * La sala no cumple las condiciones para iniciar un juego
     * (TriviaRoom::canStart() devolvió false).
     */
    public static function roomCannotStart() : self
    {
        return new self('La sala no puede iniciar un juego en este momento', 409);
    }

    /**
     * Bot gate anti-cheat: el tiempo de respuesta es menor al mínimo humano
     * plausible (ver SubmitAnswerAction::MIN_HUMAN_RESPONSE_MS) — se marca
     * como señal de anti-cheat antes de lanzar esta excepción.
     */
    public static function suspiciousResponseSpeed() : self
    {
        return new self('Respuesta inválida. Velocidad de respuesta sospechosa.', 422);
    }
}
