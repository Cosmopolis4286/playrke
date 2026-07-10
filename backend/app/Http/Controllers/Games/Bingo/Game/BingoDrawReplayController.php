<?php

namespace App\Http\Controllers\Games\Bingo\Game;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Game\BingoDrawReplayService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class BingoDrawReplayController extends Controller
{
    public function __construct(
        private readonly BingoDrawReplayService $replayService,
    ) {}

    /**
     * GET /bingo-rooms/{room}/draw/replay
     *
     * Devuelve el historial completo de fichas cantadas, ordenadas por ronda y tiempo.
     * Requiere que la sala esté en estado FINISHED o IN_PROGRESS para tener datos.
     */
    public function __invoke(BingoRoom $room): JsonResponse
    {
        $this->authorize('view', $room);

        $replay = $this->replayService->replay($room);

        return response()->json($replay);
    }
}
