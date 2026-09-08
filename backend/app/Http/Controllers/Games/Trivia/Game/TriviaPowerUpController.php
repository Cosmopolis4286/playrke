<?php

declare(strict_types=1);

namespace App\Http\Controllers\Games\Trivia\Game;

use App\Domains\Games\Trivia\Contracts\GameStateRepositoryInterface;
use App\Domains\Games\Trivia\Enums\TriviaPowerUpType;
use App\Domains\Games\Trivia\Services\TriviaPowerUpService;
use App\Exceptions\Domain\DomainException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Consume un power-up durante una partida activa.
 * Ruta: POST /trivia-game/game/{gameId}/power-up
 *
 * fifty_fifty responde de inmediato con los índices a ocultar.
 * double_points y extra_time solo activan un flag para la pregunta
 * actual — su efecto se aplica después, en TriviaAnswerController::store().
 * skip persiste una respuesta neutra (ni correcta ni incorrecta, 0 puntos)
 * y dispara el mismo avance automático que una respuesta normal.
 */
final class TriviaPowerUpController extends Controller
{
    public function __construct(
        private readonly GameStateRepositoryInterface $stateRepository,
        private readonly TriviaPowerUpService $powerUps,
    ) {}

    public function store(Request $request, string $gameId): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', array_map(fn ($t) => $t->value, TriviaPowerUpType::cases()))],
        ]);

        $type   = TriviaPowerUpType::from($validated['type']);
        $userId = (string) Auth::id();

        $gameState = $this->stateRepository->getState($gameId);
        if (! $gameState || ! $gameState->isActive()) {
            return response()->json(['success' => false, 'message' => 'Juego no activo.'], 422);
        }

        $questionData = $this->stateRepository->getCurrentQuestion($gameId);
        if (! $questionData) {
            return response()->json(['success' => false, 'message' => 'No hay pregunta activa en este momento.'], 422);
        }

        $questionId = (string) ($questionData['question_id'] ?? $questionData['id'] ?? '');

        // Ya respondió esta pregunta → ningún power-up tiene sentido (mismo
        // chequeo que TriviaAnswerController::store()).
        $alreadyAnswered = DB::connection('pgsql_trivia')
            ->table('trivia_answers')
            ->where('game_id', $gameId)
            ->where('player_id', $userId)
            ->where('question_id', $questionId)
            ->exists();

        if ($alreadyAnswered) {
            return response()->json(['success' => false, 'message' => 'Ya respondiste esta pregunta.'], 409);
        }

        try {
            $this->powerUps->consume($gameState->roomId, $userId, $type);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->httpStatus());
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return match ($type) {
            TriviaPowerUpType::FIFTY_FIFTY   => $this->handleFiftyFifty($questionData),
            TriviaPowerUpType::DOUBLE_POINTS => $this->handleDoublePoints($gameId, $userId, $questionId),
            TriviaPowerUpType::EXTRA_TIME    => $this->handleExtraTime($gameId, $userId, $questionId),
            TriviaPowerUpType::SKIP          => $this->handleSkip($gameId, $userId, $questionId, $gameState->playerIds),
        };
    }

    private function handleFiftyFifty(array $questionData): JsonResponse
    {
        $options = $questionData['options'] ?? [];
        if (is_string($options)) {
            $options = json_decode($options, true) ?? [];
        }

        $correctIndex = (int) ($questionData['correct_answer'] ?? $questionData['correct_option_index'] ?? 0);
        $hiddenIndexes = $this->powerUps->resolveFiftyFifty($correctIndex, count($options));

        return response()->json([
            'success'        => true,
            'type'           => TriviaPowerUpType::FIFTY_FIFTY->value,
            'hidden_indexes' => $hiddenIndexes,
        ]);
    }

    private function handleDoublePoints(string $gameId, string $userId, string $questionId): JsonResponse
    {
        $this->powerUps->activateDoublePoints($gameId, $userId, $questionId);

        return response()->json([
            'success' => true,
            'type'    => TriviaPowerUpType::DOUBLE_POINTS->value,
        ]);
    }

    private function handleExtraTime(string $gameId, string $userId, string $questionId): JsonResponse
    {
        $this->powerUps->activateExtraTime($gameId, $userId, $questionId);

        return response()->json([
            'success'        => true,
            'type'           => TriviaPowerUpType::EXTRA_TIME->value,
            'bonus_ms'       => \App\Domains\Games\Trivia\Services\TriviaPowerUpService::EXTRA_TIME_BONUS_MS,
        ]);
    }

    /**
     * Persiste una respuesta "saltada": no cuenta como correcta ni
     * incorrecta (0 puntos, selected_option_index=-1, is_late=false para
     * distinguirla de un timeout real), y dispara el mismo avance
     * automático que TriviaAnswerController::store() usa cuando todos ya
     * respondieron.
     */
    private function handleSkip(string $gameId, string $userId, string $questionId, array $playerIds): JsonResponse
    {
        $pgsql = DB::connection('pgsql_trivia');

        $pgsql->table('trivia_answers')->insert([
            'id'                    => (string) \Illuminate\Support\Str::ulid(),
            'game_id'               => $gameId,
            'player_id'             => $userId,
            'question_id'           => $questionId,
            'selected_option_index' => -1,
            'is_correct'            => false,
            'points_earned'         => 0,
            'response_time_ms'      => 0,
            'is_late'               => false,
            'client_id'             => 'power_up:skip',
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        $this->maybeAdvanceQuestion($gameId, $questionId, $playerIds, $pgsql);

        return response()->json([
            'success' => true,
            'type'    => TriviaPowerUpType::SKIP->value,
        ]);
    }

    /**
     * Misma lógica que TriviaAnswerController::maybeAdvanceQuestion() — se
     * duplica en vez de compartir para no acoplar ambos controllers a una
     * dependencia cruzada por un método privado; si se repite una tercera
     * vez, corresponde extraerlo a un servicio compartido.
     */
    private function maybeAdvanceQuestion(string $gameId, string $questionId, array $playerIds, object $pgsql): void
    {
        if (empty($playerIds)) {
            return;
        }

        $answeredCount = $pgsql->table('trivia_answers')
            ->where('game_id', $gameId)
            ->where('question_id', $questionId)
            ->count();

        if ($answeredCount < count($playerIds)) {
            return;
        }

        try {
            app(\App\Domains\Games\Trivia\Contracts\GameEngineInterface::class)->sendNextQuestion($gameId);
        } catch (\Throwable $e) {
            Log::error('[TriviaPowerUpController] Error avanzando pregunta tras skip', [
                'game_id' => $gameId,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
