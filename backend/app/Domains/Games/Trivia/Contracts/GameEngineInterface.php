<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Contracts;

use App\Domains\Games\Trivia\DTOs\AnswerResultDTO;
use App\Domains\Games\Trivia\DTOs\GameStateDTO;
use App\Domains\Games\Trivia\DTOs\QuestionDTO;

/**
 * Interfaz del motor de juego de trivia.
 *
 * Esta interfaz define el contrato para la lógica de juego,
 * preparada para ser implementada tanto en Laravel como en
 * futuros microservicios (Node/NestJS).
 */
interface GameEngineInterface
{
    /**
     * Cancelar un juego en curso.
     */
    public function cancelGame(string $gameId, string $reason) : void;

    /**
     * Finalizar el juego y calcular resultados finales.
     */
    public function finishGame(string $gameId) : GameStateDTO;

    /**
     * Obtener la pregunta actual del juego.
     */
    public function getCurrentQuestion(string $gameId) : ?QuestionDTO;

    /**
     * Obtener el estado actual del juego.
     */
    public function getGameState(string $gameId) : GameStateDTO;

    /**
     * Verificar si el juego está activo.
     */
    public function isGameActive(string $gameId) : bool;

    /**
     * Enviar la siguiente pregunta a todos los jugadores.
     */
    public function sendNextQuestion(string $gameId, ?string $expectedQuestionId = null) : ?QuestionDTO;

    /**
     * Cerrar la pregunta activa: deja de aceptar respuestas, revela a la sala
     * la respuesta correcta y programa la siguiente pregunta. Idempotente:
     * devuelve false si la pregunta ya no es la activa o ya estaba cerrada.
     */
    public function closeQuestion(string $gameId, string $questionId, string $reason) : bool;

    /**
     * Cerrar la pregunta solo si todos los participantes activos ya
     * respondieron (o la saltaron).
     */
    public function closeQuestionIfEveryoneAnswered(string $gameId, string $questionId) : bool;

    /**
     * Iniciar un nuevo juego en una sala.
     */
    public function startGame(string $roomId, array $playerIds) : GameStateDTO;

    /**
     * Validar una respuesta del jugador.
     */
    public function submitAnswer(
        string $gameId,
        string $playerId,
        string $answer,
        float $responseTimeMs,
    ) : AnswerResultDTO;
}
