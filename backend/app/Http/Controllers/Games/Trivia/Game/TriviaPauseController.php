<?php

declare(strict_types=1);

namespace App\Http\Controllers\Games\Trivia\Game;

use App\Domains\Games\Trivia\Models\TriviaGame;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Domains\Games\Trivia\Services\TriviaPauseService;
use App\Exceptions\Domain\DomainException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Pausa/reanuda manualmente una partida activa — solo el host de la sala o
 * admin/superadmin (misma regla que iniciar la partida, ver
 * TriviaRoomPolicy::start()). Rutas:
 *   POST /trivia-game/game/{gameId}/pause
 *   POST /trivia-game/game/{gameId}/resume
 */
final class TriviaPauseController extends Controller
{
    public function __construct(
        private readonly TriviaPauseService $pauseService,
    ) {}

    public function pause(Request $request, string $gameId): JsonResponse
    {
        $room = $this->authorizeForGame($request, $gameId);

        try {
            $this->pauseService->pause($gameId, 'manual');
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->httpStatus());
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Partida pausada.']);
    }

    public function resume(Request $request, string $gameId): JsonResponse
    {
        $room = $this->authorizeForGame($request, $gameId);

        try {
            $this->pauseService->resume($gameId);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->httpStatus());
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Partida reanudada.']);
    }

    /**
     * Resuelve la sala dueña de la partida y reutiliza
     * TriviaRoomPolicy::start() (host o admin/superadmin) — la misma regla
     * de autorización que iniciar la partida aplica para pausarla/reanudarla.
     */
    private function authorizeForGame(Request $request, string $gameId): TriviaRoom
    {
        $game = TriviaGame::findOrFail($gameId);
        $room = TriviaRoom::findOrFail($game->room_id);

        $this->authorize('start', $room);

        return $room;
    }
}
