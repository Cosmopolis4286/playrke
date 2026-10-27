<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Contracts;

use App\Domains\Games\Trivia\DTOs\LeaderboardDTO;
use App\Domains\Games\Trivia\DTOs\ScoreDTO;

/**
 * Interfaz para gestión de puntuaciones.
 */
interface ScoreServiceInterface
{
    /**
     * Obtener todas las puntuaciones del juego.
     *
     * @return array<ScoreDTO>
     */
    public function getAllScores(string $gameId) : array;

    /**
     * Obtener leaderboard en tiempo real.
     */
    public function getLeaderboard(string $gameId) : LeaderboardDTO;

    /**
     * Calcular posición de un jugador.
     */
    public function getPlayerPosition(string $gameId, string $playerId) : int;

    /**
     * Obtener puntuación de un jugador.
     */
    public function getPlayerScore(string $gameId, string $playerId) : ScoreDTO;

    /**
     * Registrar respuesta correcta con tiempo.
     */
    public function recordCorrectAnswer(
        string $gameId,
        string $playerId,
        float $responseTimeMs,
        int $basePoints = 100,
    ) : int;

    /**
     * Resetear puntuaciones del juego.
     */
    public function resetScores(string $gameId) : void;

    /**
     * Actualizar puntuación de un jugador.
     */
    public function updateScore(
        string $gameId,
        string $playerId,
        int $points,
        array $metadata = [],
    ) : ScoreDTO;
}
