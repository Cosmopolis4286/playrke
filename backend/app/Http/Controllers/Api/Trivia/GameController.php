<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Trivia;

use App\Domains\Trivia\Actions\FinishGameAction;
use App\Domains\Trivia\Actions\StartGameAction;
use App\Domains\Trivia\Contracts\GameEngineInterface;
use App\Domains\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Trivia\Contracts\ScoreServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trivia\StartGameRequest;
use App\Http\Requests\Trivia\SubmitAnswerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Controller API: Gestión de Juegos.
 */
class GameController extends Controller
{
    public function __construct(
        private readonly GameEngineInterface $gameEngine,
        private readonly RoomServiceInterface $roomService,
        private readonly ScoreServiceInterface $scoreService,
        private readonly StartGameAction $startGameAction,
        private readonly FinishGameAction $finishGameAction
    ) {}

    /**
     * Iniciar juego en una sala.
     */
    public function start(StartGameRequest $request, string $roomId): JsonResponse
    {
        $user = $request->user();
        $playerIds = $request->input('player_ids', []);

        try {
            $gameState = $this->startGameAction->execute(
                roomId: $roomId,
                hostId: (string) $user->id,
                playerIds: $playerIds
            );

            return response()->json([
                'message' => 'Juego iniciado',
                'data' => $gameState->toArray(),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Obtener estado del juego.
     */
    public function show(Request $request, string $gameId): JsonResponse
    {
        try {
            $state = $this->gameEngine->getGameState($gameId);
            $leaderboard = $this->scoreService->getLeaderboard($gameId);

            return response()->json([
                'data' => [
                    'game' => $state->toArray(),
                    'leaderboard' => $leaderboard->toArray(),
                ],
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * Obtener pregunta actual.
     */
    public function currentQuestion(Request $request, string $gameId): JsonResponse
    {
        try {
            $question = $this->gameEngine->getCurrentQuestion($gameId);

            if (!$question) {
                return response()->json([
                    'message' => 'No hay pregunta activa',
                    'data' => null,
                ]);
            }

            return response()->json([
                'data' => $question->forClient(),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Enviar respuesta.
     */
    public function submitAnswer(SubmitAnswerRequest $request, string $gameId): JsonResponse
    {
        $user = $request->user();

        try {
            $result = \App\Domains\Trivia\Actions\SubmitAnswerAction::execute(
                // Nota: En producción, inyectar via constructor
                gameId: $gameId,
                playerId: (string) $user->id,
                answer: $request->input('answer'),
                clientTimestamp: $request->input('client_timestamp')
            );

            return response()->json([
                'message' => 'Respuesta enviada',
                'data' => $result->forPlayer(),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Avanzar a siguiente pregunta (host).
     */
    public function nextQuestion(Request $request, string $gameId): JsonResponse
    {
        try {
            $question = $this->gameEngine->sendNextQuestion($gameId);

            if (!$question) {
                // Juego terminado
                return response()->json([
                    'message' => 'Juego finalizado',
                    'data' => ['finished' => true],
                ]);
            }

            return response()->json([
                'message' => 'Siguiente pregunta enviada',
                'data' => [
                    'question' => $question->forClient(),
                ],
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Finalizar juego manualmente.
     */
    public function finish(Request $request, string $gameId): JsonResponse
    {
        $user = $request->user();

        try {
            $result = $this->finishGameAction->execute(
                gameId: $gameId,
                requestedById: (string) $user->id,
                reason: 'manual'
            );

            return response()->json([
                'message' => $result['message'],
                'data' => $result['game_state']->toArray(),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Obtener leaderboard.
     */
    public function leaderboard(Request $request, string $gameId): JsonResponse
    {
        $leaderboard = $this->scoreService->getLeaderboard($gameId);

        return response()->json([
            'data' => $leaderboard->toArray(),
        ]);
    }

    /**
     * Cancelar juego.
     */
    public function cancel(Request $request, string $gameId): JsonResponse
    {
        $user = $request->user();
        $reason = $request->input('reason', 'cancelled_by_host');

        try {
            $this->gameEngine->cancelGame($gameId, $reason);

            return response()->json([
                'message' => 'Juego cancelado',
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
