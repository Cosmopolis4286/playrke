<?php

declare(strict_types=1);

namespace App\Http\Controllers\Games\Trivia\Game;

use App\Domains\Games\Trivia\Contracts\GameEngineInterface;
use App\Domains\Games\Trivia\Contracts\GameStateRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Events\Games\Trivia\TriviaAnswerResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        // ── 2. Validar que el jugador pertenece a la partida ──────────────────
        if (! in_array($userId, $gameState->playerIds, true)) {
            // Fallback: comprobar en BD (por si Redis se reinicializó después de join)
            $inDb = $pgsql->table('trivia_participants')
                ->where('room_id', $gameState->roomId)
                ->where('user_id', $userId)
                ->whereNull('deleted_at')
                ->exists();

            if (! $inDb) {
                return response()->json(['success' => false, 'message' => 'No estás inscrito en esta partida.'], 403);
            }
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
        $responseTimeMs       = (int) $request->input('response_time_ms');
        $correctOptionIndex   = (int) ($questionData['correct_answer'] ?? $questionData['correct_option_index'] ?? 0);
        $timeLimitMs          = (int) ($questionData['time_limit_seconds'] ?? 20) * 1000;

        $isLate    = $selectedOptionIndex === -1;
        $isCorrect = ! $isLate && $selectedOptionIndex === $correctOptionIndex;

        // ── 5. Calcular puntos ────────────────────────────────────────────────
        $points = 0;
        if ($isCorrect) {
            $speedFactor = max(0.0, 1.0 - ($responseTimeMs / max(1, $timeLimitMs)));
            $points      = (int) round(self::POINTS_BASE + self::POINTS_SPEED_BONUS * $speedFactor);
        }

        // ── 6. Persistir respuesta ────────────────────────────────────────────
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

        // ── 7. Actualizar score acumulado ─────────────────────────────────────
        $this->upsertScore($pgsql, $gameId, $userId, $isCorrect, $points, $responseTimeMs);

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
            $nextQuestion = $this->gameEngine->sendNextQuestion($gameId);

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
