<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Actions;

use App\Domains\Games\Trivia\Contracts\GameEngineInterface;
use App\Domains\Games\Trivia\Contracts\GameStateRepositoryInterface;
use App\Domains\Games\Trivia\DTOs\AnswerResultDTO;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Action: Enviar Respuesta.
 * 
 * Caso de uso para procesar una respuesta de jugador.
 * Maneja validaciones de tiempo y estado del juego.
 */
class SubmitAnswerAction
{
    // Tiempo máximo permitido para respuesta (segundos)
    private const MAX_RESPONSE_TIME = 30;

    // Velocidad mínima humana: respuestas en menos de 300 ms son sospechosas de bot.
    // Cubre el tiempo real de procesamiento cognitivo mínimo documentado (~200 ms).
    private const MIN_HUMAN_RESPONSE_MS = 300;

    public function __construct(
        private readonly GameEngineInterface $gameEngine,
        private readonly GameStateRepositoryInterface $stateRepository
    ) {}

    /**
     * Ejecutar la acción.
     * 
     * @param string $gameId ID del juego
     * @param string $playerId ID del jugador
     * @param string $answer Respuesta enviada
     * @param float|null $clientTimestamp Timestamp del cliente para calcular latencia
     * @throws RuntimeException Si la respuesta no es válida
     */
    public function execute(
        string $gameId,
        string $playerId,
        string $answer,
        ?float $clientTimestamp = null
    ): AnswerResultDTO {
        // Validar que el juego está activo
        if (!$this->gameEngine->isGameActive($gameId)) {
            throw new RuntimeException('El juego no está activo');
        }

        // Validar que hay una pregunta activa
        $question = $this->gameEngine->getCurrentQuestion($gameId);
        if (!$question) {
            throw new RuntimeException('No hay pregunta activa en este momento');
        }

        // Calcular tiempo de respuesta
        $responseTimeMs = $this->calculateResponseTime($clientTimestamp);

        // Validar tiempo límite
        if ($responseTimeMs > ($question->timeLimitSeconds * 1000)) {
            throw new RuntimeException('Tiempo de respuesta excedido');
        }

        // Bot gate: ningún humano responde en menos de 300 ms
        if ($clientTimestamp !== null && $responseTimeMs > 0 && $responseTimeMs < self::MIN_HUMAN_RESPONSE_MS) {
            Log::warning('[TriviaBot] Respuesta sospechosamente rápida', [
                'game_id'        => $gameId,
                'player_id'      => $playerId,
                'response_ms'    => $responseTimeMs,
            ]);
            throw new RuntimeException('Respuesta inválida. Velocidad de respuesta sospechosa.');
        }

        // Validar que la respuesta es una opción válida
        if (!in_array($answer, $question->options, true)) {
            throw new RuntimeException('Respuesta no válida');
        }

        // Validar que el jugador no ha respondido ya esta pregunta
        // (Implementar según necesidad de negocio)

        // Procesar respuesta
        $result = $this->gameEngine->submitAnswer(
            gameId: $gameId,
            playerId: $playerId,
            answer: $answer,
            responseTimeMs: $responseTimeMs
        );

        return $result;
    }

    /**
     * Calcular tiempo de respuesta en milisegundos.
     */
    private function calculateResponseTime(?float $clientTimestamp): float
    {
        $serverTime = microtime(true) * 1000;

        if ($clientTimestamp === null) {
            // Sin timestamp del cliente, usar solo servidor
            return 0; // O implementar tracking de inicio de pregunta
        }

        // Calcular diferencia servidor-cliente (aproximación)
        $latency = abs($serverTime - $clientTimestamp);

        // Limitar latencia máxima razonable
        return min($latency, self::MAX_RESPONSE_TIME * 1000);
    }
}
