<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Contracts;

use App\Domains\Games\Trivia\DTOs\GameStateDTO;
use App\Domains\Games\Trivia\DTOs\QuestionDTO;
use App\Domains\Games\Trivia\DTOs\AnswerResultDTO;

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
     * Iniciar un nuevo juego en una sala.
     */
    public function startGame(string $roomId, array $playerIds): GameStateDTO;

    /**
     * Obtener la pregunta actual del juego.
     */
    public function getCurrentQuestion(string $gameId): ?QuestionDTO;

    /**
     * Enviar la siguiente pregunta a todos los jugadores.
     */
    public function sendNextQuestion(string $gameId, ?string $expectedQuestionId = null): ?QuestionDTO;

    /**
     * Validar una respuesta del jugador.
     */
    public function submitAnswer(
        string $gameId,
        string $playerId,
        string $answer,
        float $responseTimeMs
    ): AnswerResultDTO;

    /**
     * Finalizar el juego y calcular resultados finales.
     */
    public function finishGame(string $gameId): GameStateDTO;

    /**
     * Verificar si el juego está activo.
     */
    public function isGameActive(string $gameId): bool;

    /**
     * Obtener el estado actual del juego.
     */
    public function getGameState(string $gameId): GameStateDTO;

    /**
     * Cancelar un juego en curso.
     */
    public function cancelGame(string $gameId, string $reason): void;
}
