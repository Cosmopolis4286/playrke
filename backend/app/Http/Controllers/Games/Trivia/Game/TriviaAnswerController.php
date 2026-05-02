<?php

declare(strict_types=1);

namespace App\Http\Controllers\Games\Trivia\Game;

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
 * POST /trivia/game/{gameId}/answer
 */
final class TriviaAnswerController extends Controller
{
    private const POINTS_BASE        = 1000;
    private const POINTS_SPEED_BONUS = 500;   // máximo extra por velocidad

    public function store(Request $request, string $gameId): JsonResponse
    {
        $request->validate([
            'question_id'          => ['required', 'string'],
            'selected_option_index' => ['required', 'integer', 'min:-1', 'max:3'],
            'response_time_ms'     => ['required', 'integer', 'min:0'],
            'client_id'            => ['nullable', 'string', 'max:64'],
        ]);

        $pgsql  = DB::connection('pgsql_trivia');
        $userId = (string) Auth::id();

        // ── Validar juego activo ───────────────────────────────────────────────
        $game = $pgsql->table('trivia_games')
            ->where('id', $gameId)
            ->whereIn('status', ['playing'])
            ->first();

        if (! $game) {
            return response()->json(['success' => false, 'message' => 'Juego no activo.'], 422);
        }

        // ── Validar que el jugador pertenece a la partida ─────────────────────
        $participant = $pgsql->table('trivia_participants')
            ->where('room_id', $game->room_id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if (! $participant) {
            return response()->json(['success' => false, 'message' => 'No estás inscrito en esta partida.'], 403);
        }

        $questionId           = $request->input('question_id');
        $selectedOptionIndex  = (int) $request->input('selected_option_index');
        $responseTimeMs       = (int) $request->input('response_time_ms');

        // ── Validar pregunta activa ────────────────────────────────────────────
        $gameQuestion = $pgsql->table('trivia_game_questions')
            ->where('game_id', $gameId)
            ->where('question_id', $questionId)
            ->whereIn('status', ['sent', 'active'])
            ->first();

        if (! $gameQuestion) {
            return response()->json(['success' => false, 'message' => 'Pregunta no activa.'], 422);
        }

        // ── Evitar doble respuesta ─────────────────────────────────────────────
        $alreadyAnswered = $pgsql->table('trivia_answers')
            ->where('game_id', $gameId)
            ->where('player_id', $userId)
            ->where('question_id', $questionId)
            ->exists();

        if ($alreadyAnswered) {
            return response()->json(['success' => false, 'message' => 'Ya respondiste esta pregunta.'], 409);
        }

        // ── Obtener respuesta correcta ─────────────────────────────────────────
        $question = $pgsql->table('trivia_question_bank')
            ->where('id', $questionId)
            ->first();

        if (! $question) {
            return response()->json(['success' => false, 'message' => 'Pregunta no encontrada.'], 422);
        }

        $correctOptionIndex = (int) $question->correct_option_index;
        $isLate             = $selectedOptionIndex === -1;
        $isCorrect          = ! $isLate && $selectedOptionIndex === $correctOptionIndex;
        $timeLimitMs        = ($gameQuestion->time_limit_seconds ?? $game->time_per_question) * 1000;

        // ── Calcular puntos ───────────────────────────────────────────────────
        $points = 0;
        if ($isCorrect) {
            $speedFactor = max(0.0, 1.0 - ($responseTimeMs / $timeLimitMs));
            $points = (int) round(self::POINTS_BASE + self::POINTS_SPEED_BONUS * $speedFactor);
        }

        // ── Guardar respuesta ─────────────────────────────────────────────────
        $pgsql->table('trivia_answers')->insert([
            'id'                    => (string) Str::ulid(),
            'game_id'               => $gameId,
            'player_id'             => $userId,
            'question_id'           => $questionId,
            'selected_option_index' => $isLate ? 0 : $selectedOptionIndex,
            'is_correct'            => $isCorrect,
            'points_earned'         => $points,
            'response_time_ms'      => $responseTimeMs,
            'is_late'               => $isLate,
            'client_id'             => $request->input('client_id'),
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        // ── Actualizar score acumulado del jugador ────────────────────────────
        $this->upsertScore($pgsql, $gameId, $userId, $isCorrect, $points);

        // ── Obtener score actual para el feedback ─────────────────────────────
        $currentScore = (int) ($pgsql->table('trivia_scores')
            ->where('game_id', $gameId)
            ->where('player_id', $userId)
            ->value('final_score') ?? 0);

        // ── Broadcast resultado al canal privado del jugador (best-effort) ─────
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

        return response()->json([
            'success'             => true,
            'is_correct'          => $isCorrect,
            'is_late'             => $isLate,
            'points_earned'       => $points,
            'correct_option_index'=> $correctOptionIndex,
            'current_score'       => $currentScore,
        ]);
    }

    private function upsertScore(object $pgsql, string $gameId, string $userId, bool $isCorrect, int $points): void
    {
        $existing = $pgsql->table('trivia_scores')
            ->where('game_id', $gameId)
            ->where('player_id', $userId)
            ->first();

        if ($existing) {
            $pgsql->table('trivia_scores')
                ->where('game_id', $gameId)
                ->where('player_id', $userId)
                ->update([
                    'final_score'     => $existing->final_score + $points,
                    'correct_answers' => $existing->correct_answers + ($isCorrect ? 1 : 0),
                    'wrong_answers'   => $existing->wrong_answers  + ($isCorrect ? 0 : 1),
                    'updated_at'      => now(),
                ]);
        } else {
            $pgsql->table('trivia_scores')->insert([
                'id'              => (string) Str::ulid(),
                'game_id'         => $gameId,
                'player_id'       => $userId,
                'final_score'     => $points,
                'correct_answers' => $isCorrect ? 1 : 0,
                'wrong_answers'   => $isCorrect ? 0 : 1,
                'max_streak'      => 0,
                'total_questions' => 0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }
}
