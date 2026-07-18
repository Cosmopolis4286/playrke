<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Actions;

use App\Domains\Games\Trivia\Contracts\GameEngineInterface;
use App\Domains\Games\Trivia\Contracts\GameStateRepositoryInterface;
use App\Domains\Games\Trivia\DTOs\AnswerResultDTO;
use App\Domains\Games\Trivia\DTOs\QuestionDTO;
use App\Enums\Games\Common\AntiCheatSignalType;
use App\Enums\Games\Common\GameSlug;
use App\Services\Games\AntiCheatService;
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
        private readonly GameStateRepositoryInterface $stateRepository,
        private readonly AntiCheatService $antiCheat,
    ) {}

    /**
     * Ejecutar la acción.
     *
     * @param string $gameId ID del juego
     * @param string $playerId ID del jugador
     * @param string $answer Respuesta enviada
     * @param float|null $clientTimestamp Timestamp del cliente — solo se usa como señal
     *        secundaria de desfase de reloj, NUNCA como fuente del tiempo de respuesta real.
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

        // Fuente de verdad: server_timestamp_ms persistido al enviar la pregunta.
        // Ya NO se confía en clientTimestamp para calcular la latencia real.
        $responseTimeMs = $this->calculateResponseTime($question);

        // Validar tiempo límite
        if ($responseTimeMs > ($question->timeLimitSeconds * 1000)) {
            throw new RuntimeException('Tiempo de respuesta excedido');
        }

        // Bot gate: ningún humano responde en menos de 300 ms. Se evalúa
        // siempre (antes solo si clientTimestamp !== null, lo que permitía
        // saltarse el gate omitiendo el parámetro — ya no es posible porque
        // el cálculo no depende de clientTimestamp).
        if ($responseTimeMs < self::MIN_HUMAN_RESPONSE_MS) {
            $this->antiCheat->flag(
                userId: $playerId,
                game: GameSlug::TRIVIA,
                signal: AntiCheatSignalType::INSTANT_RESPONSE,
                gameId: $gameId,
                metadata: [
                    'question_id'       => $question->questionId,
                    'response_time_ms'  => $responseTimeMs,
                    'client_timestamp'  => $clientTimestamp,
                ],
            );
            throw new RuntimeException('Respuesta inválida. Velocidad de respuesta sospechosa.');
        }

        // Señal secundaria (NO bloqueante): desfase entre cliente y servidor.
        // No rechaza la respuesta (evita falsos positivos por relojes de
        // cliente mal configurados legítimos), solo registra.
        if ($clientTimestamp !== null) {
            $drift    = abs($clientTimestamp - (microtime(true) * 1000));
            $maxDrift = (float) config('anticheat.trivia.max_clock_drift_ms', 5000);

            if ($drift > $maxDrift) {
                $this->antiCheat->flag(
                    userId: $playerId,
                    game: GameSlug::TRIVIA,
                    signal: AntiCheatSignalType::CLIENT_SERVER_TIME_MISMATCH,
                    gameId: $gameId,
                    metadata: ['drift_ms' => $drift, 'client_timestamp' => $clientTimestamp],
                );
            }
        } else {
            // Baja severidad — solo trazabilidad, el cálculo real ya no depende de este dato.
            $this->antiCheat->flag(
                userId: $playerId,
                game: GameSlug::TRIVIA,
                signal: AntiCheatSignalType::MISSING_CLIENT_TIMESTAMP,
                gameId: $gameId,
            );
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
     * Tiempo real de respuesta en milisegundos: ahora - server_timestamp_ms
     * de cuando se envió la pregunta (persistido en Redis, ver
     * GameEngineService::sendNextQuestion). Si el dato no viene (Redis
     * legacy pre-fix, o TTL/carrera), fail-closed: se trata como tiempo
     * excedido en vez de 0 — cierra el bypass que permitía clientTimestamp=null.
     */
    private function calculateResponseTime(QuestionDTO $question): float
    {
        if ($question->serverTimestampMs === null) {
            return (float) (self::MAX_RESPONSE_TIME * 1000) + 1;
        }

        $elapsed = (microtime(true) * 1000) - $question->serverTimestampMs;

        return max(0.0, min($elapsed, self::MAX_RESPONSE_TIME * 1000));
    }
}
