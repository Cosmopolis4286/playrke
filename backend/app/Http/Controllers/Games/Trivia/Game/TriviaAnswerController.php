<?php

declare(strict_types=1);

namespace App\Http\Controllers\Games\Trivia\Game;

use App\Domains\Games\Trivia\Contracts\GameEngineInterface;
use App\Domains\Games\Trivia\Contracts\GameStateRepositoryInterface;
use App\Domains\Games\Trivia\Services\TriviaPowerUpService;
use App\Domains\Games\Trivia\Services\TriviaAnswerTiming;
use App\Enums\Games\Common\AntiCheatSignalType;
use App\Enums\Games\Common\GameSlug;
use App\Http\Controllers\Controller;
use App\Events\Games\Trivia\TriviaAnswerResult;
use App\Services\Games\AntiCheatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

/**
 * Recibe y valida las respuestas de los jugadores durante una partida activa.
 * Ruta: POST /trivia-game/game/{gameId}/answer
 *
 * Lógica de avance automático:
 *   Después de guardar la respuesta se comprueba si todos los jugadores del juego
 *   ya respondieron la pregunta actual. Si sí → se envía la siguiente pregunta
 *   (o se finaliza el juego si era la última).
 *   Esto elimina la necesidad de un botón manual "Siguiente pregunta".
 */
final class TriviaAnswerController extends Controller
{
    private const POINTS_BASE        = 1000;
    private const POINTS_SPEED_BONUS = 500;

    public function __construct(
        private readonly GameStateRepositoryInterface $stateRepository,
        private readonly GameEngineInterface          $gameEngine,
        private readonly AntiCheatService              $antiCheat,
        private readonly TriviaPowerUpService          $powerUps,
    ) {}

    public function store(Request $request, string $gameId): JsonResponse
    {
        $request->validate([
            'question_id'           => ['required', 'string'],
            'selected_option_index' => ['required', 'integer', 'min:-1', 'max:3'],
            'response_time_ms'      => ['required', 'integer', 'min:0'],
            'client_id'             => ['nullable', 'string', 'max:64'],
        ]);

        $pgsql  = DB::connection('pgsql_trivia');
        $userId = (string) Auth::id();

        // ── 1. Obtener estado del juego desde Redis ───────────────────────────
        $gameState = $this->stateRepository->getState($gameId);
        if (! $gameState || ! $gameState->isActive()) {
            return response()->json(['success' => false, 'message' => 'Juego no activo.'], 422);
        }

        // ── 2. Validar la inscripción activa desde PostgreSQL ─────────────────
        // Redis puede conservar playerIds después de una salida, abandono o
        // soft-delete. La lista en memoria no es una autorización: la BD es la
        // fuente de verdad para permitir que un jugador responda.
        $isActiveParticipant = $pgsql->table('trivia_participants')
            ->where('room_id', $gameState->roomId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['abandoned'])
            ->exists();

        if (! $isActiveParticipant || ! in_array($userId, $gameState->playerIds, true)) {
            // Si Redis perdió un jugador válido, no autorizar un estado de juego
            // incoherente; primero debe restaurarse/sincronizarse el estado.
            return response()->json(['success' => false, 'message' => 'No estás inscrito activamente en esta partida.'], 403);
        }

        // ── 3. Obtener pregunta activa desde Redis ────────────────────────────
        $questionData = $this->stateRepository->getCurrentQuestion($gameId);
        if (! $questionData) {
            return response()->json(['success' => false, 'message' => 'No hay pregunta activa en este momento.'], 422);
        }

        $questionId = $request->input('question_id');

        // Verificar que el question_id enviado coincide con la pregunta activa
        $activeQuestionId = (string) ($questionData['question_id'] ?? $questionData['id'] ?? '');
        if ($questionId !== $activeQuestionId) {
            return response()->json(['success' => false, 'message' => 'Pregunta no activa.'], 422);
        }

        // ── 4. Evitar doble respuesta ─────────────────────────────────────────
        $alreadyAnswered = $pgsql->table('trivia_answers')
            ->where('game_id', $gameId)
            ->where('player_id', $userId)
            ->where('question_id', $questionId)
            ->exists();

        if ($alreadyAnswered) {
            return response()->json(['success' => false, 'message' => 'Ya respondiste esta pregunta.'], 409);
        }

        $selectedOptionIndex  = (int) $request->input('selected_option_index');
        $correctOptionIndex   = (int) ($questionData['correct_answer'] ?? $questionData['correct_option_index'] ?? 0);
        $timeLimitMs          = (int) ($questionData['time_limit_seconds'] ?? 20) * 1000;

        // Power-up "tiempo extra": bonus individual, solo para ESTE jugador
        // en esta pregunta — no afecta el timer visual de los demás ni el
        // job de avance automático (ver TriviaPowerUpService::activateExtraTime).
        $effectiveTimeLimitMs = $timeLimitMs + $this->powerUps->extraTimeBonusMs($gameId, $userId, $questionId);

        // Tiempo real server-side: ya NO se confía en response_time_ms enviado
        // por el cliente (podía mentir libremente). Fuente de verdad:
        // server_timestamp_ms persistido por GameEngineService::sendNextQuestion.
        // Se limita a $effectiveTimeLimitMs (no $timeLimitMs) para no rechazar
        // por "tiempo excedido" a un jugador con tiempo extra activo.
        $serverTimestampMs = (float) ($questionData['server_timestamp_ms'] ?? 0);

        // Sin marca de tiempo generada por el servidor no podemos validar el plazo
        // de forma segura. No convertir un estado incompleto en una respuesta válida.
        if ($serverTimestampMs <= 0) {
            Log::warning('[TriviaAnswerController] Falta server_timestamp_ms en la pregunta activa.', [
                'game_id' => $gameId,
                'question_id' => $questionId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se puede validar el tiempo de esta pregunta. Intenta reconectar.',
            ], 422);
        }

        // Conservar el tiempo real para decidir si la respuesta llegó tarde. Solo
        // el valor persistido se limita al plazo, para que no afecte métricas.
        try {
            $timing = TriviaAnswerTiming::calculate(
                $serverTimestampMs,
                microtime(true) * 1000,
                $effectiveTimeLimitMs,
            );
        } catch (\InvalidArgumentException $e) {
            Log::warning('[TriviaAnswerController] Timestamp inválido para la pregunta activa.', [
                'game_id' => $gameId,
                'question_id' => $questionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se puede validar el tiempo de esta pregunta. Intenta reconectar.',
            ], 422);
        }

        $isPastDeadline = $timing['is_late'];
        $responseTimeMs = $timing['response_time_ms'];

        // Bot gate: ningún humano responde en menos del mínimo configurado.
        if ($responseTimeMs < (int) config('anticheat.trivia.min_human_response_ms', 300)) {
            $this->antiCheat->flag(
                userId: $userId,
                game: GameSlug::TRIVIA,
                signal: AntiCheatSignalType::INSTANT_RESPONSE,
                gameId: $gameId,
                metadata: ['question_id' => $questionId, 'response_time_ms' => $responseTimeMs],
            );

            return response()->json(['success' => false, 'message' => 'Velocidad de respuesta sospechosa.'], 422);
        }

        // Una respuesta recibida después del plazo se registra como tardía aunque
        // el cliente haya enviado una opción válida. Nunca concede puntos.
        $isLate    = $selectedOptionIndex === -1 || $isPastDeadline;
        $isCorrect = ! $isLate && $selectedOptionIndex === $correctOptionIndex;

        // ── 5. Calcular puntos ────────────────────────────────────────────────
        // consumeDoublePointsFlag() se llama siempre (no solo si isCorrect)
        // para limpiar el flag de Redis tras esta pregunta pase lo que pase
        // — de un solo uso, el jugador ya pagó el uso al activarlo aunque
        // termine respondiendo mal (no hay puntos que duplicar en ese caso).
        $doublePointsActive = $this->powerUps->consumeDoublePointsFlag($gameId, $userId, $questionId);

        $points = 0;
        if ($isCorrect) {
            $speedFactor = max(0.0, 1.0 - ($responseTimeMs / max(1, $effectiveTimeLimitMs)));
            $points      = (int) round(self::POINTS_BASE + self::POINTS_SPEED_BONUS * $speedFactor);

            if ($doublePointsActive) {
                $points *= 2;
            }
        }

        // ── 6. Persistir respuesta ────────────────────────────────────────────
        // Respuesta y score deben confirmarse juntos. La restricción única de
        // (game_id, player_id, question_id) es la última defensa ante dos requests
        // simultáneas que pasen el chequeo previo de duplicados.
        try {
            $pgsql->transaction(function () use (
                $pgsql,
                $gameId,
                $userId,
                $questionId,
                $isLate,
                $selectedOptionIndex,
                $isCorrect,
                $points,
                $responseTimeMs,
                $request,
            ): void {
                $pgsql->table('trivia_answers')->insert([
                    'id'                    => (string) Str::ulid(),
                    'game_id'               => $gameId,
                    'player_id'             => $userId,
                    'question_id'           => $questionId,
                    'selected_option_index' => $isLate ? -1 : $selectedOptionIndex,
                    'is_correct'            => $isCorrect,
                    'points_earned'         => $points,
                    'response_time_ms'      => $responseTimeMs,
                    'is_late'               => $isLate,
                    'client_id'             => $request->input('client_id'),
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);

                $this->upsertScore($pgsql, $gameId, $userId, $isCorrect, $points, $responseTimeMs);
            });
        } catch (QueryException $e) {
            // PostgreSQL unique_violation: outra requisição já gravou a resposta.
            // A transação também reverte qualquer alteração parcial na pontuação.
            $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());
            if ($sqlState === '23505') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya respondiste esta pregunta.',
                ], 409);
            }

            throw $e;
        }

        // ── 7. Obtener score acumulado tras confirmar la transacción ───────────

        $currentScore = (int) ($pgsql->table('trivia_scores')
            ->where('game_id', $gameId)
            ->where('player_id', $userId)
            ->value('final_score') ?? 0);

        // ── 8. Broadcast resultado al canal privado del jugador ───────────────
        try {
            broadcast(new TriviaAnswerResult(
                playerId:           $userId,
                gameId:             $gameId,
                questionId:         $questionId,
                isCorrect:          $isCorrect,
                pointsEarned:       $points,
                correctOptionIndex: $correctOptionIndex,
                currentScore:       $currentScore,
            ));
        } catch (\Throwable $e) {
            Log::warning('[TriviaAnswerController] broadcast falló: ' . $e->getMessage());
        }

        // ── 9. Avance automático: todos respondieron → siguiente pregunta ─────
        $this->maybeAdvanceQuestion($gameId, $questionId, $gameState->playerIds, $pgsql);

        return response()->json([
            'success'              => true,
            'is_correct'           => $isCorrect,
            'is_late'              => $isLate,
            'points_earned'        => $points,
            'correct_option_index' => $correctOptionIndex,
            'current_score'        => $currentScore,
        ]);
    }

    /**
     * Si todos los jugadores activos ya respondieron la pregunta actual,
     * envía la siguiente pregunta (o finaliza el juego si era la última).
     */
    private function maybeAdvanceQuestion(
        string $gameId,
        string $questionId,
        array  $playerIds,
        object $pgsql,
    ): void {
        if (empty($playerIds)) {
            return;
        }

        // Contar respuestas para esta pregunta
        $answeredCount = $pgsql->table('trivia_answers')
            ->where('game_id', $gameId)
            ->where('question_id', $questionId)
            ->count();

        if ($answeredCount < count($playerIds)) {
            return; // Todavía faltan respuestas
        }

        // Todos respondieron → avanzar
        try {
            $nextQuestion = $this->gameEngine->sendNextQuestion($gameId, $questionId);

            if ($nextQuestion === null) {
                // sendNextQuestion devuelve null cuando llama finishGame() internamente
                Log::info('[TriviaAnswerController] Juego finalizado automáticamente', ['game_id' => $gameId]);
            }
        } catch (\Throwable $e) {
            Log::error('[TriviaAnswerController] Error avanzando pregunta', [
                'game_id' => $gameId,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function upsertScore(
        object $pgsql,
        string $gameId,
        string $userId,
        bool   $isCorrect,
        int    $points,
        int    $responseTimeMs = 0,
    ): void {
        $existing = $pgsql->table('trivia_scores')
            ->where('game_id', $gameId)
            ->where('player_id', $userId)
            ->first();

        if ($existing) {
            $answered = $existing->correct_answers + $existing->wrong_answers + 1;
            $avgTime  = (int) round(
                (($existing->average_response_time_ms ?? 0) * ($answered - 1) + $responseTimeMs) / $answered
            );

            $pgsql->table('trivia_scores')
                ->where('game_id', $gameId)
                ->where('player_id', $userId)
                ->update([
                    'final_score'              => $existing->final_score + $points,
                    'correct_answers'          => $existing->correct_answers + ($isCorrect ? 1 : 0),
                    'wrong_answers'            => $existing->wrong_answers  + ($isCorrect ? 0 : 1),
                    'average_response_time_ms' => $avgTime,
                    'updated_at'               => now(),
                ]);
        } else {
            $pgsql->table('trivia_scores')->insert([
                'id'                       => (string) Str::ulid(),
                'game_id'                  => $gameId,
                'player_id'               => $userId,
                'final_score'             => $points,
                'correct_answers'         => $isCorrect ? 1 : 0,
                'wrong_answers'           => $isCorrect ? 0 : 1,
                'average_response_time_ms'=> $responseTimeMs,
                'max_streak'              => 0,
                'total_questions'         => 0,
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);
        }
    }
}
